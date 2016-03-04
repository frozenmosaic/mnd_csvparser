select `cs_menuitem`.`itemname`, `cs_menugroup`.`menugroupname`, `cs_menucategory`.`categoryname`, `cs_categorysize`.`sizename`, `cs_price`.`price`
from `cs_menugroup`, `cs_menucategory`, `cs_menuitem`, `cs_categorysize`, `cs_price`
where `cs_menugroup`.`menugroupid` = `cs_menucategory`.`menugroupid` 
	and `cs_menucategory`.`catid` = `cs_menuitem`.`catid`
	and `cs_menuitem`.`menuitemid` =`cs_price`.`itemid`
	and `cs_menuitem`.`catid` = `cs_categorysize`.`categoryid` 
	and `cs_price`.`sizeid` = `cs_categorysize`.`sizeid`;