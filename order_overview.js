window.addEventListener("load", () => {
    const sel_elem = sel => document.querySelector(sel)
        , item_price = sel_elem("span#item_price")
        , item_count = sel_elem("span#item_count")
        , packaging = sel_elem("span#packaging_cost")
        , shipping = sel_elem("span#shipping_cost")
        , paypal = sel_elem("span#paypal_fee")
        , total = sel_elem("span#total_price")
        , count = sel_elem('input[name="count"]')
        , country = sel_elem('select[name="country_code"]');
    const update_order_overview = () => {
        const price_per_item = 1
            , item_weight = 2;
        const number_of_items = parseInt(count.value);
        item_count.textContent = number_of_items;
        item_price.textContent = (number_of_items * price_per_item).toFixed(2);
        const packaging_cost = 1;
        packaging.textContent = (packaging_cost).toFixed(2);
        let shipping_cost;
        if(country.value === "DE") {
            if(number_of_items * item_weight <= 20)
                shipping_cost = 0.7 ;
            else if(number_of_items * item_weight <= 50)
                shipping_cost = 0.85;
            else if(number_of_items * item_weight <= 500)
                shipping_cost = 1.45;
            shipping_cost += 0.9;
        }
        else
            shipping_cost = 3.2;
        shipping.textContent = (shipping_cost).toFixed(2);
        const total_price = number_of_items * price_per_item + packaging_cost + shipping_cost;
        const pp_fee = total_price * 0.19 + 0.35;
        paypal.textContent = (pp_fee).toFixed(2);
        total.textContent = (total_price + pp_fee).toFixed(2);
    };
    count.addEventListener("change", update_order_overview);
    country.addEventListener("change", update_order_overview);
    update_order_overview();
});
