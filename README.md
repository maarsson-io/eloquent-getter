# maarsson/eloquent-getter

This package adds an extendable Eloquent model getter/filter/sorter pattern to your Laravel project.

## Installation

1. Add the package to your Laravel project: `composer require maarsson/eloquent-getter`
2. Publish the config file: `php artisan vendor:publish --tag=eloquent-getter-config`

## Usage

Models can be easily filtered or sorted using the package. Filter keys in the request without a matching filter method in the getter class are ignored. A `sort_by` value without a matching sorter method sorts by the table column of the same name, or is ignored if there is no such column.

1. Create a getter class for your existing model: `php artisan make:getter 'YourModel'`.

2. Add the `Maarsson\EloquentGetter\Traits\GetterableTrait` trait to the model:
    ```php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Maarsson\EloquentGetter\Traits\GetterableTrait;

    class YourModel extends Model
    {
        use GetterableTrait;
    }
    ```

3. Add the required filter method(s) to the created `YourModelGetter` class. Filter method names must be camel cased and must end with `Filter`:
    ```php
    protected function nameFilter(string|null $searchString): \Illuminate\Database\Eloquent\Builder
    {
        return $this->builder->where('name', 'LIKE', '%' . $searchString . '%');
    }
    ```

4. Get the filtered collection using the `filter[]` parameter in the query:
    ```php
    // HTTP GET //localhost/yourmodel?filter[name]=foo
    public function index(\App\Getters\YourModelGetter $getter)
    {
        return (new YourModel())
            ->filter($getter)
            ->get();
    }
    ```

5. Add the required sorter method(s) to the created `YourModelGetter` class. Sorter method names must be camel cased and must end with `Sorter`:
    ```php
    protected function relatedModelDateSorter(): \Illuminate\Database\Eloquent\Builder
    {
        return RelatedModel::select('date')
            ->whereColumn('this_model_column', 'related_table.column');
    }
    ```

6. Get the sorted collection using the `sort_by` parameter in the query:
    ```php
    // HTTP GET //localhost/yourmodel?sort_by=related_model_date
    public function index(\App\Getters\YourModelGetter $getter)
    {
        return (new YourModel())
            ->order($getter)
            ->get();
    }
    ```

### Combined filtering, sorting and paginating

Get the filtered, sorted and paginated result using the helper methods:

```php
// HTTP GET //localhost/yourmodel?filter[name]=foo&page=5&per_page=20&sort_by=related_model_date&sort_order=desc
public function index(\App\Getters\YourModelGetter $getter)
{
    return (new YourModel())
        ->filter($getter)
        ->order()
        ->paginate();
}
```

The following request parameters are considered:
- `filter[]` default: `null`
- `page` default: `1`
- `per_page` default: `20`
- `sort_by` default: `'id'`
- `sort_order` default: `'asc'`

### Loading attributes and relations

You can even control which model attributes are returned, including relations (and their attributes). Just pass a simple dot-notated array to the `withAttributes()` method. This example also shows how to combine it with pagination:

```php
// HTTP GET //localhost/yourmodel?filter[name]=foo&page=5&per_page=20&sort_by=related_model_date&sort_order=desc
public function index(\App\Getters\YourModelGetter $getter)
{
    return (new YourModel())
        ->filter($getter)
        ->order()
        ->paginate()
        ->through(
            fn ($item) => $item->withAttributes([
                'id',
                'name', // a model property
                'finalPrice', // even a model accessor
                'users', // a relation (with all of its attributes)
                'users.posts:id,title', // a relation's relation (with limited attributes)
            ])
        );
}
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
