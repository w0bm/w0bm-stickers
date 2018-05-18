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
        const price_per_item = 100
            , item_weight = 2;
        const number_of_items = parseInt(count.value);
        item_count.textContent = number_of_items;
        item_price.textContent = ((number_of_items * price_per_item) / 100).toFixed(2);
        const packaging_cost = 100;
        packaging.textContent = (packaging_cost / 100).toFixed(2);
        let shipping_cost;
        if(country.value === "DE") {
            if(number_of_items * item_weight <= 20)
                shipping_cost = 70;
            else if(number_of_items * item_weight <= 50)
                shipping_cost = 85;
            else if(number_of_items * item_weight <= 500)
                shipping_cost = 145;
            shipping_cost += 90;
        }
        else
            shipping_cost = 320;
        shipping.textContent = (shipping_cost / 100).toFixed(2);
        const total_price = number_of_items * price_per_item + packaging_cost + shipping_cost;
        const pp_fee = Math.ceil((total_price * 0.019 + 35) / 0.981);
        paypal.textContent = (pp_fee / 100).toFixed(2);
        total.textContent = ((total_price + pp_fee) / 100).toFixed(2);
    };
    count.addEventListener("change", update_order_overview);
    country.addEventListener("change", update_order_overview);
    update_order_overview();
});
