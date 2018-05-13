function init_flash() {
    const cfg = {
        flash: {
            elem: document.querySelector("div#flash"),
            keyframe: [ { top: "-22px" }, { top: 0 } ],
            duration: 4000, // 4 seconds
            options: {
                duration: 400,
                fill: "both"
            },
            types: [
                "error",
                "success",
                "warn"
            ]
        }
    };
    let _tmp = {
        flash: false
    };
    return flash = ({ type, msg }) => {
        cfg.flash.elem.innerHTML = msg;
        if(cfg.flash.types.includes(type)) {
            cfg.flash.elem.className = "";
            cfg.flash.elem.classList.add(type);
        }
        if(_tmp.flash)
            return false;
        _tmp.flash = true;
        if(typeof cfg.flash.elem.animate !== "function") { // Edgy af
            const key = Object.keys(cfg.flash.keyframe[0])[0];
            cfg.flash.elem.style.top = cfg.flash.keyframe[1][key];
            setTimeout(() => {
                cfg.flash.elem.style.top = cfg.flash.keyframe[0][key];
                _tmp.flash = false;
            }, cfg.flash.duration);
            return false;
        }
        cfg.flash.elem.animate(
            cfg.flash.keyframe,
            cfg.flash.options
        ).onfinish = () => setTimeout(() => {
            cfg.flash.elem.animate(
                cfg.flash.keyframe,
                Object.assign({ direction: "reverse" }, cfg.flash.options)
            ).onfinish = () => _tmp.flash = false;
        }, cfg.flash.duration);
        return false;
    };
}
