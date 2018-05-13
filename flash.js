function init_flash() {
    const cfg = {
        flash: {
            elem: document.querySelector("div#flash"),
            keyframe: [ { top: "-22px" }, { top: 0 } ],
            duration: 4000, // 4 seconds
            options: {
                duration: 400,
                fill: "forwards"
            },
            types: {
                error: {
                    backgroundColor: "#fddfdf",
                    border: "2px solid #f1a899",
                    textColor: "#5f3f3f"
                },
                success: {
                    backgroundColor: "#4caf50",
                    border: "2px solid #006018",
                    textColor: "#001c07"
                },
                warn: {
                    backgroundColor: "#fffa90",
                    border: "2px solid #dad55e",
                    textColor: "#777620"
                }
            }
        }
    };
    let _tmp = {
        flash: false
    };
    return flash = (type, msg) => {
        cfg.flash.elem.innerHTML = msg;
        if(cfg.flash.types.hasOwnProperty(type)) {
            cfg.flash.elem.style.backgroundColor = cfg.flash.types[type].backgroundColor;
            cfg.flash.elem.style.borderBottom = cfg.flash.types[type].border;
            cfg.flash.elem.style.fontColor = cfg.flash.types[type].fontColor;
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
