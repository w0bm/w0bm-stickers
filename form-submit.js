document.addEventListener("DOMContentLoaded", () => document
    .querySelectorAll("form.js-submit")
    .forEach(f => f.addEventListener("submit", e => {
        let cb = window[f.dataset.submitCallback];
        cb && cb(f);
        e.preventDefault();
        fetch(f.action, {
                method: f.method,
                body: new FormData(f)
            })
            .then(async res => {
                const body = await (res.headers.get("content-type") === "application/json" ? res.json() : res.text());
                cb = window[f.dataset.callback];
                cb && cb(res, body, f);
            })
            .catch(error => {
                cb = window[f.dataset.errorCallback];
                cb && cb(error, f);
            });
    }))
);
