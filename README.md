# CFPB WP CLI

One or more custom commands we use a bunch at CFPB to manage our site.

## Commands

Just one for now

- [Taxonomy migration ()](#taxonomy-migration)

## Taxonomy Migration

Migrate taxonomies like this:

`wp taxonomy migrate <from> <to>`

It takes the following optional argument:
- `--post_type` a comma separated list of all post types to migrate
- `--exclude` a comma separated list of any post id to exclude
- `--newer_than` a date string formatted like 2013-01-31 will only run the
    command on posts newer than January 31, 2013
- `--older_than` opposite of newer than
- `--term` a comma separated list of specific terms to migrate

The command will loop through each post and create new taxonomy-term
relationships for each on the target taxonomy. If post 'foo' had tags 'bar' and
'baz' it will now have groups 'bar' and 'baz' as well

### Examples
`wp taxonomy migrate tag group` will migrate taxonomy-term relationships for
tags on all posts in the WordPress database to a taxonomy called group
`wp taxonomy migrate tag group --post_type=post` will migrate the tags on only
posts and exclude all other taxonomies with tags
`wp taxonomy migrate tag group --exclude=1,2,3,5,8` will exclude posts 1, 2, 3,
5, and 8 from the migration.
