document.addEventListener("DOMContentLoaded", async () => {
    const res = await fetch("https://restcountries.eu/rest/v2/all?fields=name;alpha2Code");
    if(!res.ok)
        throw new Error("Failed to fetch country codes" + JSON.stringify(res) + JSON.stringify(body));
    const json = await res.json();
    document
        .querySelectorAll("select.country_select")
        .forEach(sel => {
            const def = sel.dataset.selected;
            sel.innerHTML =
                json
                .map(c => `<option value="${c.alpha2Code}" ${c.alpha2Code === def && "selected"}>${c.name}</option>`);
        });
   });
