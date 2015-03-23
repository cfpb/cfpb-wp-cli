# CFPB WP CLI Commands

Custom commands we use at CFPB to manage our site.

## Installation

Install this as a plugin to your WordPress site. Then, if you have WP-CLI 
installed, you'll have access to the commands below.

## Requirements

WordPress 3.7 or higher running on PHP 5.3 or higher.

## Commands

Just two for now:

- Migration
    - [Taxonomy migration](#taxonomy-migration)
    - [Author migration](#author-migration)
- [Randomize taxonomy](#randomize)

## Taxonomy Migration

Migrate taxonomies like this:

`wp migrate taxonomy <from> <to>`

It takes the following optional argument:
- `--post_type` a comma separated list of all post types to migrate
- `--exclude` a comma separated list of any post id to exclude
- `--after` a date string formatted like 2013-01-31 will only run the
    command on posts newer than January 31, 2013
- `--before` opposite of after
- `--term` a comma separated list of specific terms to migrate (currently unimplemented)

The command will loop through each post and create new taxonomy-term
relationships for each on the target taxonomy. If post 'foo' had tags 'bar' and
'baz' it will now have groups 'bar' and 'baz' as well

### Examples
`wp migrate taxonomy tag group` will migrate taxonomy-term relationships for
tags on all posts in the WordPress database to a taxonomy called group
`wp migrate taxonomy tag group --post_type=post` will migrate the tags on only
posts and exclude all other taxonomies with tags
`wp migrate taxonomy tag group --exclude=1,2,3,5,8` will exclude posts 1, 2, 3,
5, and 8 from the migration.

## Author Migration

Migrate authors like this:

`wp migrate author`

By default it will attempt to migrate all native authors to a taxonomy called 
"author" failing that it will save the author in custom fields. Pass the type
argument to specify a different taxonomy in which to save authors.

It takes the following optional arguments:
- `type` the name of a custom taxonomy to migrate authors to (default: author)
- `--include` a comma separated list of any post id to include (will exclude all
  others, default: all)
- `--exclude` a comma separated list of any post id to exclude (default: none)
- `--post_type` the name of the post type to target (default: post)
- `--authors` a comma separated list of authors to migrate, will exclude all others
- `--before` a date string formatted like 2013-01-31 will only run the command on
  posts older than January 31, 2013
- `--after` opposite of before
- `--ignore_term` a list of taxonomy terms to ignore. The first item in this comma
  separated list should be the taxonomy and all following should be the terms

### Examples

`wp migrate author` will migrate all native authors (read: users) on posts to a
taxonomy called "author" or a custom field if that taxonomy does not exist
`wp migrate author post_tag` will migrate all authors on posts to tags
`wp migrate author --post_type=custom` will migrate all authors on post objects of
the post type `custom`
`wp migrate author --post_type=custom --ignore_term=category,featured,sticky` will
migrate authors on all posts in the "custom" post type that do not have categories
featured or sticky


## Randomize

Allows you to assign random taxonomy terms to a post or set of posts. 

Usage: `wp randomize taxonomy category`

By default all objects of the 'post' post type will be randomized, use the
`--post_type` flag to target pages or a custom post type. Use the `--include` 
and `--exclude` flags to filter or ignore specific object IDs and the `--before`
and `--after` flags to specify a date range. Also, optionally pass `--terms` as
a list of terms you want to use for the randomization. Failing that, if terms exist 
in the target taxonomy, those terms will be used. If not, a string of 6 words 
generated randomly will be used for the randomization.
