### TEST LOGGING

***Duplicate Checking Logic - Menu Mods:***
A group is considered unique if there is no other group with the exact name in the same location.
=> unique identifier: group name + location id
If there is no duplicate: 
Add group and store group id 
Else:
Do nothing and retrieve group id from database

An item is considered unique if there is no other item with the exact name in the same group.
=> unique identifier: item name + group id
If there is no duplicate: 
Add category and store item id 
Else: 
Do nothing and retrieve item id 

-/-
**TEST CASES:**
if duplicate group {
    modifications to group attributes - type, min, max - are not allowed
    if duplicate item {
        do nothing 
    } else {
        add everything - item, subprices - as new (2)
    }
} else {
    add everything - group, type, min, max, item, subprices - as new (1)
}

-/-
**EDGE CASES:**
data declared in subsequent rows instead of the first row
=> invalid data gets imported instead