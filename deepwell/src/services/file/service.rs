/*
 * services/file/service.rs
 *
 * DEEPWELL - Wikijump API provider and database manager
 * Copyright (C) 2019-2022 Wikijump Team
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use super::prelude::*;
use crate::models::file::{self, Entity as File, Model as FileModel};
use crate::services::blob::CreateBlobOutput;
use crate::services::file_revision::CreateFirstFileRevision;
use crate::services::{BlobService, FileRevisionService};

#[derive(Debug)]
pub struct FileService;

impl FileService {
    /// Uploads a file and tracks it as a separate file entity.
    ///
    /// In the background, this stores the blob via content addressing,
    /// meaning that duplicates are not uploaded twice.
    pub async fn create(
        ctx: &ServiceContext<'_>,
        CreateFile {
            revision_comments,
            name,
            site_id,
            page_id,
            user_id,
            licensing,
        }: CreateFile,
        data: &[u8],
    ) -> Result<CreateFileOutput> {
        let txn = ctx.transaction();

        tide::log::info!(
            "Creating file with name '{}', content length {}",
            name,
            data.len(),
        );

        Self::check_conflicts(ctx, &name, page_id).await?;

        // Upload to S3, get derived metadata
        let CreateBlobOutput { hash, mime, .. } = BlobService::create(ctx, data).await?;

        // Insert into database
        let file_id = ctx.cuid()?;
        let size_hint: i64 = data.len().try_into().expect("Buffer size exceeds i64");

        let model = file::ActiveModel {
            file_id: Set(file_id.clone()),
            name: Set(name.clone()),
            page_id: Set(page_id),
            ..Default::default()
        };
        model.insert(txn).await?;

        // Add new file revision
        let revision_output = FileRevisionService::create_first(
            ctx,
            CreateFirstFileRevision {
                site_id,
                page_id,
                file_id: file_id.clone(),
                user_id,
                name,
                s3_hash: hash,
                size_hint,
                mime_hint: mime,
                licensing,
                comments: revision_comments,
            },
        )
        .await?;

        Ok(revision_output.into())
    }

    /// Updates metadata associated with this file.
    pub async fn update(ctx: &ServiceContext<'_>, file_id: &str) -> Result<()> {
        // TODO update file, updated_at

        // TODO: how to preserve history of changes?
        //       maybe file_revision table (and then trashing file page_revision_type)

        todo!()
    }

    /// Deletes this file.
    ///
    /// Like other deletions throughout Wikijump, this is a soft deletion.
    /// It marks the files as deleted but retains the contents, permitting it
    /// to be easily reverted.
    pub async fn delete(
        ctx: &ServiceContext<'_>,
        file_id: String,
        input: DeleteFile,
    ) -> Result<FileModel> {
        let txn = ctx.transaction();

        let DeleteFile {
            revision_comments,
            site_id,
            page_id,
            user_id,
        } = input;

        // Ensure file exists
        if !Self::exists(ctx, &file_id).await? {
            return Err(Error::NotFound);
        }

        // Set deletion flag
        let model = file::ActiveModel {
            file_id: Set(file_id.clone()),
            deleted_at: Set(Some(now())),
            ..Default::default()
        };
        let file = model.update(txn).await?;

        // Add new file revision
        // TODO

        Ok(file)
    }

    // TODO
    /// Restores a deleted file.
    ///
    /// This undeletes a file, moving it from the deleted sphere to the specified location.
    #[allow(dead_code)]
    pub async fn restore(_ctx: &ServiceContext<'_>, _file_id: String) -> Result<()> {
        todo!()
    }

    /// Gets an uploaded file that has been, including its contents if requested.
    pub async fn get_optional(
        ctx: &ServiceContext<'_>,
        file_id: &str,
        blob: bool,
    ) -> Result<Option<GetFileOutput>> {
        todo!()
    }

    /// Gets an uploaded file, failing if it does not exists.
    pub async fn get(
        ctx: &ServiceContext<'_>,
        file_id: &str,
        blob: bool,
    ) -> Result<GetFileOutput> {
        match Self::get_optional(ctx, file_id, blob).await? {
            Some(file) => Ok(file),
            None => Err(Error::NotFound),
        }
    }

    pub async fn exists(ctx: &ServiceContext<'_>, file_id: &str) -> Result<bool> {
        Self::get_optional(ctx, file_id, false)
            .await
            .map(|file| file.is_some())
    }

    /// Hard deletes this file and all duplicates.
    ///
    /// This is a very powerful method and needs to be used carefully.
    /// It should only be accessible to platform staff.
    ///
    /// As opposed to normal soft deletions, this method will completely
    /// remove a file from Wikijump. The file rows will be deleted themselves,
    /// and will cascade to any places where file IDs are used.
    ///
    /// This method should only be used very rarely to clear content such
    /// as severe copyright violations, abuse content, or comply with court orders.
    pub async fn hard_delete_all(ctx: &ServiceContext<'_>, file_id: &str) -> Result<()> {
        // TODO find hash. update all files with the same hash
        // TODO add to audit log
        // TODO hard delete BlobService

        todo!()
    }

    /// Checks to see if a file already exists at the name specified.
    ///
    /// If so, this method fails with `Error::Conflict`. Otherwise it returns nothing.
    async fn check_conflicts(
        ctx: &ServiceContext<'_>,
        name: &str,
        page_id: i64,
    ) -> Result<()> {
        let txn = ctx.transaction();

        let result = File::find()
            .filter(
                Condition::all()
                    .add(file::Column::Name.eq(name))
                    .add(file::Column::PageId.eq(page_id))
                    .add(file::Column::DeletedAt.is_null()),
            )
            .one(txn)
            .await?;

        match result {
            None => Ok(()),
            Some(file) => {
                tide::log::error!(
                    "File {} with name '{}' already exists on page ID {}, cannot create",
                    file.file_id,
                    name,
                    page_id,
                );

                Err(Error::Conflict)
            }
        }
    }
}
