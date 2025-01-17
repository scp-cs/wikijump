//! SeaORM Entity. Generated by sea-orm-codegen 0.6.0

use sea_orm::entity::prelude::*;
use serde::{Deserialize, Serialize};

#[derive(Clone, Debug, PartialEq, DeriveEntityModel, Serialize, Deserialize)]
#[sea_orm(table_name = "forum_group")]
pub struct Model {
    #[sea_orm(primary_key)]
    pub group_id: i64,
    pub name: Option<String>,
    pub description: Option<String>,
    pub sort_index: i32,
    pub site_id: Option<i32>,
    pub visible: bool,
}

#[derive(Copy, Clone, Debug, EnumIter, DeriveRelation)]
pub enum Relation {
    #[sea_orm(
        belongs_to = "super::site::Entity",
        from = "Column::SiteId",
        to = "super::site::Column::SiteId",
        on_update = "Cascade",
        on_delete = "Cascade"
    )]
    Site,
    #[sea_orm(has_many = "super::forum_category::Entity")]
    ForumCategory,
}

impl Related<super::site::Entity> for Entity {
    fn to() -> RelationDef {
        Relation::Site.def()
    }
}

impl Related<super::forum_category::Entity> for Entity {
    fn to() -> RelationDef {
        Relation::ForumCategory.def()
    }
}

impl ActiveModelBehavior for ActiveModel {}
