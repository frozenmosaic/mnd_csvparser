### CSV PARSER

## Objective
Import CSV-formatted data of menu groups, categories, items and modifiers into existing database system through a simplistic uploader form to speed up menu building process.
There are two seperate processes for this program: importing menu groups/categories/items/sizes data, and importing menu modifiers data.

## Operation Process
The operation of this program follows these steps:
1. Read uploaded file in CSV format.
2. Process and validate data, particularly checking for empty files, removing empty rows and checking for missing important values.
3. If data is valid, proceed to insert to database.
4. For each spreadsheet, the program ensures unique imports of menu groups, categories, items, sizes and modifier groups are imported.
5. Print status messages, including errors if any or success message 

## Assumptions/Constraints
1. No location will have duplicate menu groups.
2. No menu groups will have duplicate menu categories. Thus, updating an existing menu group from an existing location is not permitted; class CSVParser will ignore new data in such case.
3. No location will have duplicate modifier groups.
