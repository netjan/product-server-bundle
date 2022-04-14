## Installation

```
composer require netjan/product-server-bundle
```

## Configuration

File `.env.local`
```
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7"
```

File: `config/routes.yaml`

```
netjan_product:
    prefix: '/api'
    resource: "@NetJanProductServerBundle/config/routing.xml"
```

File: `config/packages/fos_rest.yaml`
```
fos_rest:
    param_fetcher_listener: true
    body_listener: true
    format_listener:
        enabled: true
        rules:
            - { path: '^/', priorities: ['json', 'xml'], fallback_format: 'html' }
    versioning: true
    view:
        view_response_listener: 'force'
```



## Create table

```
bin/console doctrine:schema:update --force
```

## Start

```
php -S 127.0.0.1:8000 -t public
```

## Usage

```
curl -X 'GET' \
  'http://127.0.0.1:8000/api/products' \
  -H 'accept: application/json'
```
