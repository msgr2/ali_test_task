# SMSEdge V2 Dev Docs

- [General](#general)
    - [Starting project](#starting-project)
    - [Shortcuts](#shortcuts)
- [Docs management](#docs-management)
    - [Examples](#examples)

<a name="general"></a>

## General

### Starting project

- `git clone..`
- `composer install`
- `sail up -d`

### Shortcuts

`http://v2.local/admin` - app

- u: admin@admin.com
- p: password

`http://v2.local/telescope/` - logs, exceptions etc'

`http://v2.local/docs` - docs

`http://v2.local/horizon` - queues management

`http://v2.local/log-viewer` - logs

> {info} if route doesn't work run - `php artisan route:clear`
>



<a name="docs-management"></a>

## Docs management

We use [laraceipt](https://github.com/saleem-hadad/larecipe) to manage (examples for their docs
is [here](https://github.com/larecipe/larecipe-docs/blob/main/resources/docs/2.2/configurations.md?plain=1)).
All docs are in `resources/docs/v1.0` folder. You can find the sidebar is in `index.md`. Create a .md file in folder for
new features/docs

<a name="examples"></a>

#### Examples

some text

breakline

---

```php
echo 'example code'
```

---

`echo 'example inline`

---

> {warning} example warning

---

> {info} example info
