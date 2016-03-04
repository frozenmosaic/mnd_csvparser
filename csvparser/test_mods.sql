select `cs_toppinggroup`.`toppinggroupname`, 
	`cs_toppinggroup`.`group_type`, 
	`cs_toppingitems`.`left_price`,
	`cs_toppingitems`.`whole_price`,
	`cs_toppingitems`.`right_price`,
	`cs_toppingitems`.`toppingitemname`
from `cs_toppinggroup`, `cs_toppingitems`
where `cs_toppinggroup`.`toppinggroupid` = `cs_toppingitems`.`toppinggroupid`;
