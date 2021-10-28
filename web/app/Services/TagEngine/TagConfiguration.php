<?php
declare(strict_types=1);

namespace Wikijump\Services\TagEngine;

use Carbon\Carbon;
use Ds\Set;

class TagConfiguration
{
    /**
     * @var array $tags The rules and definitions concerning tags.
     *
     * Schema: [
     *   'tag_name' => [
     *     'properties' => [
     *        ?'role_ids' => [list of role IDs that can apply this tag],
     *        ?'date_bound' => [start_date, end_date],
     *      ],
     *     'condition_lists' => [
     *       zero or more:
     *       array matching TagConditionList
     *     ],
     *   ],
     * ]
     */
    private array $tags;

    /**
     * @var array $tag_groups The rules and definitions concerning tags.
     *
     * Schema: [
     *   'group_name' => [
     *     'members' => [list of tag names],
     *     'condition_lists' => [
     *       zero or more:
     *       array matching TagConditionList
     *     ],
     *   ],
     * ]
     */
    private array $tag_groups;

    // Creation and serialization
    public function __construct(array $data)
    {
        $this->tags = $data['tags'] ?? [];
        $this->tag_groups = $data['tag_groups'] ?? [];

        // Convert $tags
        foreach ($this->tags as &$tag) {
            if (isset($tag['properties']['role_ids'])) {
                $tag['properties']['role_ids'] = new Set($tag['properties']['role_ids']);
            }
        }

        // Convert $tag_groups
        foreach ($this->tag_groups as &$tag_group) {
            $tag_group['members'] = new Set($tag_group['members']);
        }
    }

    /**
     * Exposes a view of this class that is suitable for serializing into JSON.
     * @return array
     */
    public function toJson(): array
    {
        return [
            'tags' => $this->tags,
            'tag_groups' => $this->tag_groups,
        ];
    }

    // Validation

    /**
     * Validates whether the added and removed tags all exist and can be changed in this context.
     *
     * Result schema:
     * [
     *   'tag-name' => ['undefined' | 'role' | 'date'...],
     * ]
     *
     * @param Set $added_tags Which tags were added
     * @param Set $removed_tags Which tags were removed
     * @param Set $role_ids The roles that the current user performing the tag action has
     * @param Carbon $date
     * @return array The result of the determination
     */
    public function validateTags(
        Set $added_tags,
        Set $removed_tags,
        Set $role_ids,
        Carbon $date
    ): array {
        $result = [];

        // If empty, it means don't validate on tag existence
        if (empty($this->tags)) {
            return $result;
        }

        // Check all added and removed tags
        foreach ($added_tags as $tag) {
            $this->checkCanChangeTag($tag, $date, $role_ids, $result);
        }

        foreach ($removed_tags as $tag) {
            $this->checkCanChangeTag($tag, $date, $role_ids, $result);
        }

        return $result;
    }

    /**
     * Validates whether all tag conditions passed for this set of new tags.
     *
     * Result schema:
     * [
     *   'tags' => [
     *     'tag-name' => [
     *       For each condition:
     *       [
     *         'valid' => bool,
     *         'passed' => int,
     *         'threshold' => int,
     *       ],
     *       ...
     *     ],
     *   ],
     *   'tag_groups' => [
     *     'tag-group-name' => [
     *       For each condition:
     *       [
     *         'valid' => bool,
     *         'passed' => int,
     *         'threshold' => int,
     *       ],
     *       ...
     *     ],
     *   ],
     * ]
     *
     * @param Set $tags The tags being proposed
     * @return array The result of the determination
     */
    public function validateConditions(Set $tags): array
    {
        $result = [
            'tags' => [],
            'tag_groups' => [],
        ];

        // Check tag condition lists
        foreach ($this->tags as $tag => $data) {
            if ($tags->contains($tag)) {
                $results = [];

                foreach ($data['condition_lists'] as $index => $condition_list_data) {
                    $condition_list = new TagConditionList($condition_list_data);
                    $results[] = $condition_list->validate($tags);
                }

                $result['tags'][$tag] = $results;
            }
        }

        // Check tag group condition lists
        foreach ($this->tag_groups as $tag_group => $data) {
            if (!$this->tagGroupPresent($tag_group, $tags)->isEmpty()) {
                $results = [];

                foreach ($data['condition_lists'] as $index => $condition_list_data) {
                    $condition_list = new TagConditionList($condition_list_data);
                    $results[] = $condition_list->validate($tags);
                }

                $result['tag_groups'][$tag_group] = $results;
            }
        }

        // All condition lists passed
        return $result;
    }

    // Tag helpers
    private function checkCanChangeTag(
        string $tag,
        Set $role_ids,
        Carbon $date,
        array &$result
    ): void {
        $reasons = [];

        $tag_data = $this->tags[$tag];
        if ($tag_data === null) {
            // No tag entry, not a valid tag
            $reasons[] = 'undefined';
            $result[$tag] = $reasons;
            return;
        }

        // Check role constraint, if present
        $allowed_role_ids = $tag_data['properties']['role_ids'];
        if ($allowed_role_ids !== null) {
            if ($allowed_role_ids->intersect($role_ids)->isEmpty()) {
                // No roles in common, not allowed to apply
                $reasons[] = 'role';
            }
        }

        // Check date constraint, if present
        $date_bound = $tag_data['properties']['date_bound'];
        if ($date_bound !== null) {
            [$start_date, $end_date] = $date_bound;
            if (!$date->between($start_date, $end_date)) {
                $reasons[] = 'date';
            }
        }

        // If there are reasons for rejection, add them
        if (!empty($reasons)) {
            $result[$tag] = $reasons;
        }
    }

    // Tag group helpers
    public function tagGroupMembers(string $name): Set
    {
        return $this->tag_groups[$name]['members'];
    }

    public function tagGroupPresent(string $name, Set $tags): Set
    {
        return $this->tagGroupMembers($name)->intersect($tags);
    }

    public function tagGroupFullyPresent(string $name, Set $tags): bool
    {
        return $this->tagGroupMembers($name)
            ->diff($tags)
            ->isEmpty();
    }

    public function tagGroupFullyAbsent(string $name, Set $tags): bool
    {
        return $this->tagGroupPresent($name, $tags)->isEmpty();
    }
}
