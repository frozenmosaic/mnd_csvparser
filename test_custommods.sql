select `cs_toppinggroup`.`toppinggroupname`, 
	`cs_toppinggroup`.`group_type`, 
	`cs_toppingitems`.`left_price`,
	`cs_toppingitems`.`whole_price`,
	`cs_toppingitems`.`right_price`,
	`cs_toppingitems`.`toppingitemname`,
	`cs_custom_topping_labels`.`topping_label`	
from `cs_toppinggroup`, `cs_toppingitems`, `cs_custom_topping_labels`
where `cs_toppinggroup`.`toppinggroupid` = `cs_toppingitems`.`toppinggroupid` 
	and 
	`cs_custom_topping_labels`.`topping_group_id` =					`cs_toppingitems`.`toppinggroupid`;