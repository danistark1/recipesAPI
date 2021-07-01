# recipesAPI

REST APIs for Recipe Manager project by https://github.com/PascaleStark

# APIs


| Endpoint | ex | Function|
| ------------- | ------------- | ------------- |
| GET /recipes  |   |  Get all recipes|
| GET /recipes/name/{name} |   | Get recipe by name|
| GET recipes/where |  recipes/where?id=1 | Get recipe with a condition |
| GET /recipes/search |  GET /recipes/search?q=pizza&page=1&filter={field}| Search for recipe with a query, paginated|
| GET recipes/file/{id} |  recipe media file id | Get a recipe media file |
| POST recipes/category |   | post a recipe category|
| POST recipes/upload/{id} |   | post a recipe media file |
| POST /recipes |   | Post a recipe |
| DELETE recipes/delete/{id} |   | Delete a recipe|
| PATCH recipes/favourites/{id}} |   | Set a recipe as favourite |
| PATCH recipes/featured/{id} |   | Set a recipe as featured |
| PATCH recipes/update/{id} |   | Update a recipe field |

### PAGINATION
Results are pagniated by default. 

[Paginator](https://github.com/danistark1/recipesAPI/blob/56188d03f725c3b9260c652595a3a442b7005b67/src/RecipesPaginator.php#L14)

### API SCHEMAS

- [POST Schema](https://github.com/danistark1/recipesAPI/blob/e72d887aff4d20b5800a77e6989412bb2f892825/src/RecipesPostSchema.php#L14)
- [PATCH Schema](https://github.com/danistark1/recipesAPI/blob/630cf61b148e26f5888c037aacfc1d9c5d280906/src/RecipesPatchSchema.php#L14)
- [POST CATEGORY SCHEMA](https://github.com/danistark1/recipesAPI/blob/52f96d6966c4cdea21e18979988792364e52a2c5/src/CategorySchema.php#L11)

### CORS LISTNER (TEST Env)

[Cors Listener](https://github.com/danistark1/recipesAPI/blob/a0887c10d501cd5595468c2c254a7a43d1024265/src/CorsListener.php#L10)

### CACHE Handler

[Cache Handler](https://github.com/danistark1/recipesAPI/blob/3711659bb06cb882eb83c3596d626047201c953c/src/RecipesCacheHandler.php#L20-L19)


### LOGGER

[Logger](https://github.com/danistark1/recipesAPI/blob/44a4f2230540b7e9db197a817f844141f5117de2/src/RecipesLogger.php#L14)
