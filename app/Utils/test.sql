select
    SUM(vld.qty_available) as stock,
    `variations`.`sub_sku` as `sku`,
    `variations`.`dpp_inc_tax` as `dpp_inc_tax`,
    `p`.`name` as `product`,
    `p`.`type`,
    `p`.`id` as `product_id`,
    `units`.`short_name` as `unit`,
    `p`.`enable_stock` as `enable_stock`,
    `variations`.`sell_price_inc_tax` as `unit_price`,
    `pv`.`name` as `product_variation`,
    `variations`.`name` as `variation_name`,
    `l`.`name` as `location_name`,
    `l`.`id` as `location_id`,
    `variations`.`id` as `variation_id`
from
    `variations`
    inner join `products` as `p` on `p`.`id` = `variations`.`product_id`
    inner join `units` on `p`.`unit_id` = `units`.`id`
    left join `variation_location_details` as `vld` on `variations`.`id` = `vld`.`variation_id`
    left join `business_locations` as `l` on `vld`.`location_id` = `l`.`id`
    inner join `product_variations` as `pv` on `variations`.`product_variation_id` = `pv`.`id`
where
    `p`.`business_id` = ?
    and `p`.`is_inactive` = ?
    and `p`.`type` in (?, ?)
    and `variations`.`deleted_at` is null
group by
    `variations`.`id`,
    `vld`.`location_id` DELIMITER $ $ CREATE DEFINER = `root` @`localhost` PROCEDURE `stock_report`(
        IN `vld_location_id` INT(11),
        IN `variation_id` INT(11),
        IN `opening_date` DATETIME,
        IN `start_date` DATETIME,
        IN `end_date` DATETIME,
        IN `business_id` INT(11),
        IN `is_inactive` INT(11),
        IN `types` VARCHAR(11),
    ) BEGIN
SELECT
    (
        select
            SUM(vld.qty_available) as stock,
            `variations`.`sub_sku` as `sku`,
            `variations`.`dpp_inc_tax` as `dpp_inc_tax`,
            `p`.`name` as `product`,
            `p`.`type`,
            `p`.`id` as `product_id`,
            `units`.`short_name` as `unit`,
            `p`.`enable_stock` as `enable_stock`,
            `variations`.`sell_price_inc_tax` as `unit_price`,
            `pv`.`name` as `product_variation`,
            `variations`.`name` as `variation_name`,
            `l`.`name` as `location_name`,
            `l`.`id` as `location_id`,
            `variations`.`id` as `variation_id`
    ),
    (
        select
            SUM(OPL.quantity - OPL.quantity_returned)
        FROM
            transactions
            JOIN purchase_lines AS OPL ON transactions.id = OPL.transaction_id
        WHERE
            transactions.status = 'received'
            AND transactions.type = 'opening_stock'
            AND transactions.location_id = vld_location_id
            AND OPL.variation_id = variation_id
            AND `transactions`.`transaction_date` <= opening_date
    ) AS openingstock,
    (
        select
            SUM(SL.quantity - SL.quantity_returned)
        FROM
            transactions
            JOIN transaction_sell_lines AS SL ON transactions.id = SL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell'
            AND transactions.location_id = vld_location_id
            AND SL.variation_id = variation_id
            AND (
                `transactions`.`transaction_date` BETWEEN start_date
                AND end_date
            )
    ) as sell_qty,
    (
        select
            SUM(PL.quantity - PL.quantity_returned)
        FROM
            transactions
            JOIN purchase_lines AS PL ON transactions.id = PL.transaction_id
        WHERE
            (
                transactions.type = 'purchase_return'
                OR transactions.type != 'opening_stock'
            )
            AND transactions.location_id = vld_location_id
            AND PL.variation_id = variation_id
            AND (
                `transactions`.`transaction_date` BETWEEN start_date
                AND end_date
            )
    ) as purchase_qty,
    (
        select
            SUM(TSL.quantity - TSL.quantity_returned)
        FROM
            transactions
            JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell'
            AND transactions.location_id = vld_location_id
            AND TSL.variation_id = variation_id
    ) as total_sold,
    (
        select
            SUM(
                IF(
                    transactions.type = 'sell_transfer',
                    TSL.quantity,
                    0
                )
            )
        FROM
            transactions
            JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell_transfer'
            AND transactions.location_id = vld_location_id
            AND TSL.variation_id = variation_id
            AND (
                `transactions`.`transaction_date` BETWEEN start_date
                AND end_date
            )
    ) as total_transfered,
    (
        SELECT
            SUM(
                IF(
                    transactions.type = 'stock_adjustment',
                    SAL.quantity,
                    0
                )
            )
        FROM
            transactions
            JOIN stock_adjustment_lines AS SAL ON transactions.id = SAL.transaction_id
        WHERE
            transactions.type = 'stock_adjustment'
            AND transactions.location_id = vld_location_id
            AND SAL.variation_id = variation_id
            AND (
                `transactions`.`transaction_date` BETWEEN start_date
                AND end_date
            )
    ) as total_adjusted
from
    `variations`
    inner join `products` as `p` on `p`.`id` = `variations`.`product_id`
    inner join `units` on `p`.`unit_id` = `units`.`id`
    left join `variation_location_details` as `vld` on `variations`.`id` = `vld`.`variation_id`
    left join `business_locations` as `l` on `vld`.`location_id` = `l`.`id`
    inner join `product_variations` as `pv` on `variations`.`product_variation_id` = `pv`.`id`
where
    `p`.`business_id` = business_id
    and `p`.`is_inactive` = is_inactive
    and `p`.`type` in (types)
    and `variations`.`deleted_at` is null
group by
    `variations`.`id`,
    `vld`.`location_id`;

END $ $ DELIMITER;

-- --------------------------------------------------------
DELIMITER $ $ CREATE DEFINER = `root` @`localhost` PROCEDURE `stock_report`(
    IN `opening_date` DATETIME,
    IN `start_date` DATETIME,
    IN `end_date` DATETIME,
    IN `business_id` INT(11),
    IN `is_inactive` INT(11),
    IN `types` VARCHAR(11),
) BEGIN
select
    (
        SELECT
            SUM(
                OPL.quantity - OPL.quantity_returned
            )
        FROM
            transactions
            JOIN purchase_lines AS OPL ON transactions.id = OPL.transaction_id
        WHERE
            (
                transactions.status = 'received'
                AND transactions.type = 'opening_stock'
            )
            AND transactions.location_id = vld.location_id
            AND OPL.variation_id = variations.id
            AND `transactions`.`transaction_date` <= opening_date
    ) as openingstock,
    (
        SELECT
            SUM(
                SL.quantity - SL.quantity_returned
            )
        FROM
            transactions
            JOIN transaction_sell_lines AS SL ON transactions.id = SL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell'
            AND transactions.location_id = vld.location_id
            AND SL.variation_id = variations.id
            AND (
                `transactions`.`transaction_date` BETWEEN start_date
                AND end_date
            )
    ) as sell_qty,
    (
        SELECT
            SUM(
                PL.quantity - PL.quantity_returned
            )
        FROM
            transactions
            JOIN purchase_lines AS PL ON transactions.id = PL.transaction_id
        WHERE
            (
                transactions.type = 'purchase_return'
                OR transactions.type != 'opening_stock'
            )
            AND transactions.location_id = vld.location_id
            AND PL.variation_id = variations.id
            AND (
                `transactions`.`transaction_date` BETWEEN start_date
                AND end_date
            )
    ) as purchase_qty,
    (
        SELECT
            SUM(
                TSL.quantity - TSL.quantity_returned
            )
        FROM
            transactions
            JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell'
            AND transactions.location_id = vld.location_id
            AND TSL.variation_id = variations.id
    ) as total_sold,
    (
        SELECT
            SUM(
                IF(
                    transactions.type = 'sell_transfer',
                    TSL.quantity,
                    0
                )
            )
        FROM
            transactions
            JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell_transfer'
            AND transactions.location_id = vld.location_id
            AND (TSL.variation_id = variations.id)
            AND (
                `transactions`.`transaction_date` BETWEEN start_date
                AND end_date
            )
    ) as total_transfered,
    (
        SELECT
            SUM(
                IF(
                    transactions.type = 'stock_adjustment',
                    SAL.quantity,
                    0
                )
            )
        FROM
            transactions
            JOIN stock_adjustment_lines AS SAL ON transactions.id = SAL.transaction_id
        WHERE
            transactions.type = 'stock_adjustment'
            AND transactions.location_id = vld.location_id
            AND (SAL.variation_id = variations.id)
            AND (
                `transactions`.`transaction_date` BETWEEN start_date
                AND end_date
            )
    ) as total_adjusted,
    SUM(vld.qty_available) as stock,
    `variations`.`sub_sku` as `sku`,
    `variations`.`dpp_inc_tax` as `dpp_inc_tax`,
    `p`.`name` as `product`,
    `p`.`type`,
    `p`.`id` as `product_id`,
    `units`.`short_name` as `unit`,
    `p`.`enable_stock` as `enable_stock`,
    `variations`.`sell_price_inc_tax` as `unit_price`,
    `pv`.`name` as `product_variation`,
    `variations`.`name` as `variation_name`,
    `l`.`name` as `location_name`,
    `l`.`id` as `location_id`,
    `variations`.`id` as `variation_id`
from
    `variations`
    inner join `products` as `p` on `p`.`id` = `variations`.`product_id`
    inner join `units` on `p`.`unit_id` = `units`.`id`
    left join `variation_location_details` as `vld` on `variations`.`id` = `vld`.`variation_id`
    left join `business_locations` as `l` on `vld`.`location_id` = `l`.`id`
    inner join `product_variations` as `pv` on `variations`.`product_variation_id` = `pv`.`id`
where
    `p`.`business_id` = business_id
    and `p`.`is_inactive` = is_inactive
    and `p`.`type` in (types)
    and `variations`.`deleted_at` is null
group by
    `variations`.`id`,
    `vld`.`location_id`
END $ $ DELIMITER;

--   and `p`.`type` in (types) 
select
    (
        SELECT
            SUM(OPL.quantity - OPL.quantity_returned)
        FROM
            transactions
            JOIN purchase_lines AS OPL ON transactions.id = OPL.transaction_id
        WHERE
            (
                transactions.status = 'received'
                AND transactions.type = 'opening_stock'
            )
            AND transactions.location_id = vld.location_id
            AND OPL.variation_id = variations.id
            AND `transactions`.`transaction_date` <= '2022-04-01 23:59:59'
    ) as openingstock,
    (
        SELECT
            SUM(SL.quantity - SL.quantity_returned)
        FROM
            transactions
            JOIN transaction_sell_lines AS SL ON transactions.id = SL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell'
            AND transactions.location_id = vld.location_id
            AND SL.variation_id = variations.id
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as sell_qty,
    (
        SELECT
            SUM(PL.quantity - PL.quantity_returned)
        FROM
            transactions
            JOIN purchase_lines AS PL ON transactions.id = PL.transaction_id
        WHERE
            (
                transactions.type = 'purchase_return'
                OR transactions.type != 'opening_stock'
            )
            AND transactions.location_id = vld.location_id
            AND PL.variation_id = variations.id
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as purchase_qty,
    (
        SELECT
            SUM(TSL.quantity - TSL.quantity_returned)
        FROM
            transactions
            JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell'
            AND transactions.location_id = vld.location_id
            AND TSL.variation_id = variations.id
    ) as total_sold,
    (
        SELECT
            SUM(
                IF(
                    transactions.type = 'sell_transfer',
                    TSL.quantity,
                    0
                )
            )
        FROM
            transactions
            JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell_transfer'
            AND transactions.location_id = vld.location_id
            AND (TSL.variation_id = variations.id)
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as total_transfered,
    (
        SELECT
            SUM(
                IF(
                    transactions.type = 'stock_adjustment',
                    SAL.quantity,
                    0
                )
            )
        FROM
            transactions
            JOIN stock_adjustment_lines AS SAL ON transactions.id = SAL.transaction_id
        WHERE
            transactions.type = 'stock_adjustment'
            AND transactions.location_id = vld.location_id
            AND (SAL.variation_id = variations.id)
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as total_adjusted,
    SUM(vld.qty_available) as stock,
    `variations`.`sub_sku` as `sku`,
    `variations`.`dpp_inc_tax` as `dpp_inc_tax`,
    `p`.`name` as `product`,
    `p`.`type`,
    `p`.`id` as `product_id`,
    `units`.`short_name` as `unit`,
    `p`.`enable_stock` as `enable_stock`,
    `variations`.`sell_price_inc_tax` as `unit_price`,
    `pv`.`name` as `product_variation`,
    `variations`.`name` as `variation_name`,
    `l`.`name` as `location_name`,
    `l`.`id` as `location_id`,
    `variations`.`id` as `variation_id`
from
    `variations`
    inner join `products` as `p` on `p`.`id` = `variations`.`product_id`
    inner join `units` on `p`.`unit_id` = `units`.`id`
    left join `variation_location_details` as `vld` on `variations`.`id` = `vld`.`variation_id`
    left join `business_locations` as `l` on `vld`.`location_id` = `l`.`id`
    inner join `product_variations` as `pv` on `variations`.`product_variation_id` = `pv`.`id`
    inner join `product_locations` as `pl` on `pl`.`product_id` = `p`.`id`
where
    `p`.`business_id` = ?
    and `p`.`is_inactive` = ?
    and `p`.`type` in (?, ?)
    and `vld`.`location_id` = ?
    and (`pl`.`location_id` = ?)
    and `variations`.`deleted_at` is null
group by
    `variations`.`id`,
    `vld`.`location_id` -- -----------------------------------------------------------------------
select
    (
        SELECT
            SUM(OPL.quantity - OPL.quantity_returned)
        FROM
            transactions
            JOIN purchase_lines AS OPL ON transactions.id = OPL.transaction_id
        WHERE
            (
                transactions.status = 'received'
                AND transactions.type = 'opening_stock'
            )
            AND transactions.location_id = vld.location_id
            AND OPL.variation_id = variations.id
            AND `transactions`.`transaction_date` <= '2022-04-01 23:59:59'
    ) as openingstock,
    (
        SELECT
            SUM(SL.quantity - SL.quantity_returned)
        FROM
            transactions
            JOIN transaction_sell_lines AS SL ON transactions.id = SL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell'
            AND transactions.location_id = vld.location_id
            AND SL.variation_id = variations.id
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as sell_qty,
    (
        SELECT
            SUM(PL.quantity - PL.quantity_returned)
        FROM
            transactions
            JOIN purchase_lines AS PL ON transactions.id = PL.transaction_id
        WHERE
            (
                transactions.type = 'purchase_return'
                OR transactions.type != 'opening_stock'
            )
            AND transactions.location_id = vld.location_id
            AND PL.variation_id = variations.id
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as purchase_qty,
    (
        SELECT
            SUM(TSL.quantity - TSL.quantity_returned)
        FROM
            transactions
            JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell'
            AND transactions.location_id = vld.location_id
            AND TSL.variation_id = variations.id
    ) as total_sold,
    (
        SELECT
            SUM(
                IF(
                    transactions.type = 'sell_transfer',
                    TSL.quantity,
                    0
                )
            )
        FROM
            transactions
            JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell_transfer'
            AND transactions.location_id = vld.location_id
            AND (TSL.variation_id = variations.id)
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as total_transfered,
    (
        SELECT
            SUM(
                IF(
                    transactions.type = 'stock_adjustment',
                    SAL.quantity,
                    0
                )
            )
        FROM
            transactions
            JOIN stock_adjustment_lines AS SAL ON transactions.id = SAL.transaction_id
        WHERE
            transactions.type = 'stock_adjustment'
            AND transactions.location_id = vld.location_id
            AND (SAL.variation_id = variations.id)
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as total_adjusted,
    SUM(vld.qty_available) as stock,
    `variations`.`sub_sku` as `sku`,
    `variations`.`dpp_inc_tax` as `dpp_inc_tax`,
    `p`.`name` as `product`,
    `p`.`type`,
    `p`.`id` as `product_id`,
    `units`.`short_name` as `unit`,
    `p`.`enable_stock` as `enable_stock`,
    `variations`.`sell_price_inc_tax` as `unit_price`,
    `pv`.`name` as `product_variation`,
    `variations`.`name` as `variation_name`,
    `l`.`name` as `location_name`,
    `l`.`id` as `location_id`,
    `variations`.`id` as `variation_id`
from
    `variations`
    inner join `products` as `p` on `p`.`id` = `variations`.`product_id`
    inner join `units` on `p`.`unit_id` = `units`.`id`
    left join `variation_location_details` as `vld` on `variations`.`id` = `vld`.`variation_id`
    left join `business_locations` as `l` on `vld`.`location_id` = `l`.`id`
    inner join `product_variations` as `pv` on `variations`.`product_variation_id` = `pv`.`id`
where
    `p`.`business_id` = ?
    and `p`.`is_inactive` = ?
    and `p`.`type` in (?, ?)
    and `variations`.`deleted_at` is null
group by
    `variations`.`id`,
    `vld`.`location_id` 
    
--------------------------------------------------------

select
    (
        SELECT
            SUM(OPL.quantity - OPL.quantity_returned)
        FROM
            transactions
            JOIN purchase_lines AS OPL ON transactions.id = OPL.transaction_id
        WHERE
            (
                transactions.status = 'received'
                AND transactions.type = 'opening_stock'
            )
            AND transactions.location_id = vld.location_id
            AND OPL.variation_id = variations.id
            AND `transactions`.`transaction_date` <= '2022-04-01 23:59:59'
    ) as openingstock,
    (
        SELECT
            SUM(SL.quantity - SL.quantity_returned)
        FROM
            transactions
            JOIN transaction_sell_lines AS SL ON transactions.id = SL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell'
            AND transactions.location_id = vld.location_id
            AND SL.variation_id = variations.id
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as sell_qty,
    (
        SELECT
            SUM(PL.quantity - PL.quantity_returned)
        FROM
            transactions
            JOIN purchase_lines AS PL ON transactions.id = PL.transaction_id
        WHERE
            (
                transactions.type = 'purchase_return'
                OR transactions.type != 'opening_stock'
            )
            AND transactions.location_id = vld.location_id
            AND PL.variation_id = variations.id
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as purchase_qty,
    (
        SELECT
            SUM(TSL.quantity - TSL.quantity_returned)
        FROM
            transactions
            JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell'
            AND transactions.location_id = vld.location_id
            AND TSL.variation_id = variations.id
    ) as total_sold,
    (
        SELECT
            SUM(
                IF(
                    transactions.type = 'sell_transfer',
                    TSL.quantity,
                    0
                )
            )
        FROM
            transactions
            JOIN transaction_sell_lines AS TSL ON transactions.id = TSL.transaction_id
        WHERE
            transactions.status = 'final'
            AND transactions.type = 'sell_transfer'
            AND transactions.location_id = vld.location_id
            AND (TSL.variation_id = variations.id)
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as total_transfered,
    (
        SELECT
            SUM(
                IF(
                    transactions.type = 'stock_adjustment',
                    SAL.quantity,
                    0
                )
            )
        FROM
            transactions
            JOIN stock_adjustment_lines AS SAL ON transactions.id = SAL.transaction_id
        WHERE
            transactions.type = 'stock_adjustment'
            AND transactions.location_id = vld.location_id
            AND (SAL.variation_id = variations.id)
            AND (
                `transactions`.`transaction_date` BETWEEN '2022-04-01 00:00:00'
                AND '2023-03-31'
            )
    ) as total_adjusted,
    SUM(vld.qty_available) as stock,
    `variations`.`sub_sku` as `sku`,
    `variations`.`dpp_inc_tax` as `dpp_inc_tax`,
    `p`.`name` as `product`,
    `p`.`type`,
    `p`.`id` as `product_id`,
    `units`.`short_name` as `unit`,
    `p`.`enable_stock` as `enable_stock`,
    `variations`.`sell_price_inc_tax` as `unit_price`,
    `pv`.`name` as `product_variation`,
    `variations`.`name` as `variation_name`,
    `l`.`name` as `location_name`,
    `l`.`id` as `location_id`,
    `variations`.`id` as `variation_id`
from
    `variations`
    inner join `products` as `p` on `p`.`id` = `variations`.`product_id`
    inner join `units` on `p`.`unit_id` = `units`.`id`
    left join `variation_location_details` as `vld` on `variations`.`id` = `vld`.`variation_id`
    left join `business_locations` as `l` on `vld`.`location_id` = `l`.`id`
    inner join `product_variations` as `pv` on `variations`.`product_variation_id` = `pv`.`id`
    inner join `product_locations` as `pl` on `pl`.`product_id` = `p`.`id`
where
    `p`.`business_id` = ?
    and `p`.`is_inactive` = ?
    and `p`.`type` in (?, ?)
    and `vld`.`location_id` = ?
    and (`pl`.`location_id` = ?)
    and `variations`.`deleted_at` is null
group by
    `variations`.`id`,
    `vld`.`location_id`