OM (OctoMarket) - Сatalogue, with multi-categories and custom
fields for OctoberCMS (Laravel5)
> This is not an e-commerce plugin, there is no orders and payments management.

#Capabilities
* one base - many stores
* Nested categories
* Moving products between categories
* Simple breadcrumbs
* SEO tags
* .html postfix in URL for catalogs and items
* RainLab.Sitemap compatibility

##Documentation

This plug-in allows you to quickly implement the functionality of
the catalog with multi-categories and your own custom fields.
OktoMarket basically uses "Twig". This makes it more attractive to Twig fans.
### Static data [Component]

Component "Static data" creates a variable on the page **om_static** from which you can output in twig static data
 
 
**Example:** Create left menu which categories and products
``` 
<ul>
    {% for category in om_static.get({shop_id:1}) %}
        <li><a href="{{ category.url }}">{{ category.name }}</a></li>
        {% if category.categories %}
            <ul>
                {% for category in category.categories %}
                <li><a href="{{ category.url }}">{{ category.name }}</a></li>
                    <ul>
                        {% if category.storeitems %}
                            {% for item in category.storeitems %}
                                <li><a href="{{ item.url }}">{{ item.name }}</a></li>
                            {% endfor %}
                        {% endif %}
                    </ul>
                {% endfor %}
            </ul>
        {% endif %}
    {% endfor %}
</ul>
```

As you can see from the example, the variable om_static has the get method.
* om_static.get - Output all categories and products
* om_static.get({store_id:1}) - Display store categories with id = 1
* om_static.get({category_id:1}) - Display subcategories of the category with id = 1

#### Categories

When bypassing collection, each instance contains the following variables:
* id (int) - id
* name (string) - Category name
* active (bool) - Enabled/disabled
* short_desc (text) - Short description
* full_desc (text) - Full description
* seo_title (string) - Meta title
* seo_description (string) - Meta description
* seo_keywords (string) - Meta keywords
* slug (string) - Category slug
* url (string) - Category url
* categories (collection) - Subcategories
* parent (object) - Parent category
* store (object) - Store of category 
* images (collection) - Images of the category 
* items (collection) - Products of the category 
* storeItems($options) (collection) - Products of the category
* hasItems (collection) - All products of the category and its subcategories

#### Items
**Example:** Items list
```
{% for item in category.storeitems %}
    <li><a href="{{ item.url }}">{{ item.name }}</a></li>
{% endfor %}
```
**Example:** Items list wich options
```
{% for item in category.storeitems({store_id:1,paginate:10,page:4}) %}
    <li><a href="{{ item.url }}">{{ item.name }}</a></li>
{% endfor %}
```
Available options for 'storeitems' method
* store_id - Items from store by id
* paginate - Number of items per page
* page - Current page number

**Example:** Display pagination
```
{% set items = om_request.category.storeItems({paginate:10}) %}

{% for item in items %}
    <li><a href="{{ item.url }}">{{ item.name }}</a></li>
{% endfor %}

<div class="pagination">
    {{ items.render|raw }}
</div>
```


The following variables will be available for the instance:
* id (int) - id
* name (string) - Product name
* active (bool) - Enabled/disabled
* short_desc (text) - Short description
* full_desc (text) - Full description
* seo_title (string) - Meta title
* seo_description (string) - Meta description
* seo_keywords (string) - Meta keywords
* slug (string) - Product slug
* url (string) - Product url
* price (decimal 0.00) - Price
* quantity (int) - Quantity
* vendor_code (string) - Vendor code (or article)
* hits (int) - Number of product views
* store (object) - Store of product
* storage (object) - Storage of product
* category (object) - Category of product
* images (collection) - Images of the product 
* custom() - Value of an custom field

#### Custom fields (custom) 

Custom fields are an excellent tool for flexible structuring of data. You can create as many custom fields as you want. The product can
contain any number of additional fields whose types can be
duplicate and at the same time contain different data.

Custom fields contain the following types of fields for storing information:
* int_val - Integer value
* str_val - String value
* html_val - Plain text or html

**Example:** Single-field value request
By field code:
```
{{ item.custom('top').values|first.int_val }}
```
By field id:
```
{{ item.custom(1).values|first.int_val }}
```

**Example:** Get field name
```
{{ item.custom('top').name }}
```
If you want to get the field name and its values, it's better to use the twig operator
"set" to not create additional queries:

**Example:** 
```
{% set custom = item.custom('top') %}<br>
Field name: {{ custom.name }}<br>

{% set custom_values = custom.values|first %}<br>
Integer value: {{ custom_values.int_val }}<br>
String value: {{ custom_values.str_val }}<br>
HTML: {{ custom_values.html_val|raw }}<br>
```

#### Collections by additional fields (field_items)

This method has the required parameter {code:%custom field code%}

**Example:** Get item collection by additional field
```
{% for item in om_static.field_items({code:'top'}) %}
    {{ item.name }} - {{ item.custom('top').values|first.int_val }}</br>
{% endfor %}
```

Extra options
* order - Sort by selected field. For example order:'int_val' in reverse order will be
order:'int_val:desc'
* limit - Limiting the number of output records

**Example:**
```
{% for item in om_static.field_items({code:'top', order:'int_val:desc', limit:2}) %}
    {{ item.name }} - {{ item.custom('top').values|first.int_val }}</br>
{% endfor %}
```

#### Accessories (getAccessories)
Display products selected as accessories
```
{% if om_request.item %}
    {% set item = om_request.item %}
    <h2>{{ item.name }}</h2>
      {% for accessory in item.getAccessories %}
        {{ accessory.name }}<br>
    {% endfor %}
{% endif %}
```

### Request data [Component]
Component "Request data" creates on the page a variable **om_request** that depends on the URL

**Attention!**
The page on which the component is connected must have a specific URL. For example If there is a store with a "catalog" code, then to output the dynamic data for this store by URL, page (cmsPage) should have the URL / catalog /:? *

**Example:**
```
{# Output of categories and products in them by URL #}
{% if om_request.categories %}
    <ul>
        <h2>{{ om_request.category.name }}</h2>
    {% for category in om_request.categories %}
        <li><a href="{{ category.url }}">{{ category.name }}</a> <b>[{{ category.items.count }}]</b></li>
        {% if category.items %}
            <ul>
            {% for item in category.items %}
                <li><a href="{{ item.url }}">{{ item.name }}</a></li>
            {% endfor %}
            </ul>
        {% endif %}
    {% endfor %}
    </ul>
{% endif %}

{# List the products in this category #}
{% if om_request.category.storeItems.count %}
    <h4>Список товаров</h4>
    {% for item in om_request.category.storeItems %}
        <li><a href="{{ item.url }}">{{ item.name }}</a></li>
    {% endfor %}
{% endif %}

{# Output of the product card #}
{% if om_request.item %}
    {% set item = om_request.item %}
    <h2>{{ item.name }}</h2>
{% endif %}
```
#### Bread crumbs (Breadcrumbs)

Bread crumbs are contained in the om_request.breadcrumbs method and return an array of the form:
```
[
    [
        'slug' => '/catalog/category/item',
        'name' => 'Product name'
    ]
]
```
**Example:** Output "bread crumbs"
```
{% if om_request.breadcrumbs %}
<ol class="breadcrumb">
    {% for item in om_request.breadcrumbs %}
        <li><a href="{{ item.slug }}">{{ item.name }}</a></li>
    {% endfor %}
    {% if om_request.item %}
        <li class="active">{{ om_request.item.name }}</li>
    {% endif %}
</ol>
{% endif %}
```