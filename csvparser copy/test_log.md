### TEST LOGGING

***Duplicate Checking Logic - Menu Items:***
A group is considered unique if there is no other group with the exact name in the same location.
=> unique identifier: group name + location id
If there is no duplicate: 
Add group and store group id 
Else:
Do nothing and retrieve group id from database

A category is considred unique if there is no other category with the exact name in the same group.
=> unique identifier: category name + group id
If there is no duplicate:
Add category and store category id 
Else: 
Do nothing and retrieve category id from database 

An item is considered unique if there is no other item with the exact name in the same category.
=> unique identifier: item name + category id
If there is no duplicate: 
Add category and store item id 
Else: 
Do nothing and retrieve item id 

A size name is considered unique if there is no other size name in the same category.
=> unique identifier: size name + category id 
A size name is a secondary attribute, which means that its existence is dependent on another element, in this case the category.
If there is no duplicate:
Case 1: Category does not exist 
Add size name  

Case 2: Category exists, yet this is a new size name 
Add new size name 
Update category size number 
Update item price 

Else: (if the size name exists, the category it belongs to has to exist as well)
Do nothing and retrieve size name id

A price listing is considered unique if there is no other item with the same name and the same size name.
=> unique identifier: item name + size name id
If there is no duplicate: this means that there is no price associated to the item with the size name provided
Add this new price 

Else: (if there is a duplicate)
Update the price (no need for validation, because item id and size name id are already obtained based on specified constraints)

-/-

**TEST CASES:**

if duplicate group {
    if duplicate category {
        if duplicate size name { (empty size name counts as new size name)
            if duplicate item {
                if duplicate price listing {
                    do nothing (3)
                } else {
                    update price listing (3)
                }
            } else {
                add everything - item, price listing - as new (3)
            }
        } else {
            add everything - size name, item, price listing - as new 
            update size number 
            (3)
        }         
    } else {
        add everything - category, size name, item, price listing - as new (2)
    }
} else {
    add everything - group, category, size name, item, price listing - as new (1)
}




