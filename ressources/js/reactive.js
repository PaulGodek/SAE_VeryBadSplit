// Fiché copié collé depuis le TD7 de JS
export {applyAndRegister, reactive,  startReactiveDom}

let registeringEffect = null;
let objectByName = new Map();

let objectDependencies = new Map();

function registerEffect(target, key){
    // On ajoute l'effet à la liste des effets des propriétés [key] de l'objet target
    if (!objectDependencies.get(target).has(key)){
        objectDependencies.get(target).set(key, new Set());
    }
    objectDependencies.get(target).get(key).add(registeringEffect);
}


// applique effect une première fois et l'ajoute à registeredEffects
function applyAndRegister(effect){
    registeringEffect = effect;
    effect();
    registeringEffect = null;
}

// parcourt registeredEffects pour appliquer tous les effets enregistrés.
function trigger(target, key){
    if (!objectDependencies.get(target).has(key)){
        return;
    }
    for (let effect of objectDependencies.get(target).get(key)){
        effect();
    }
}

window.trigger=trigger;

function reactive(passiveObject, name){
    objectDependencies.set(passiveObject, new Map());
    const handler ={
        get(target, key){
            if (registeringEffect !== null)
                registerEffect(target, key);
            return target[key];
        },
        set(target, key, value){
            target[key] = value;
            trigger(target, key);
            return true;
        },
    };

    let reactiveObject = new Proxy(passiveObject, handler);
    objectByName.set(name, reactiveObject);
    return reactiveObject;
}

function startReactiveDom(){
    for (let elementClickable of document.querySelectorAll("[data-onclick]")){
        const [nomObjet, methode, argument] = elementClickable.dataset.onclick.split(/[.()]+/);
        elementClickable.addEventListener('click', (event) => {
            const objet = objectByName.get(nomObjet);
            objet[methode](argument);
        })
    }

    for (let elem of document.querySelectorAll("[data-textfun]")){
        const [obj, fun, arg] = elem.dataset.textfun.split(/[.()]+/);
        applyAndRegister(()=>{elem.textContent = objectByName.get(obj)[fun](arg)});
    }
    for (let elem of document.querySelectorAll("[data-textvar]")){
        const [obj, prop] = elem.dataset.textvar.split('.');
        applyAndRegister(()=>{elem.textContent = objectByName.get(obj)[prop]});
    }
    for (let elem of document.querySelectorAll("[data-stylefun]")){
        const [obj, fun, arg] = elem.dataset.stylefun.split(/[.()]+/);
        applyAndRegister(()=>{Object.assign(elem.style, objectByName.get(obj)[fun](arg))});
    }
    
    // Ajout du support pour data-htmlfun
    for (let elem of document.querySelectorAll("[data-htmlfun]")){
        const [obj, fun, arg] = elem.dataset.htmlfun.split(/[.()]+/);
        applyAndRegister(()=>{elem.innerHTML = objectByName.get(obj)[fun](arg)});
    }
    
}
