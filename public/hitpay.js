function bootstrap(){let s=!1,o=null,i=null,a=null,t="visible",n={visible:!1,defaultUrl:"",initOptions:{scheme:"",domain:""},callbacks:{onClose:null,onSuccess:null,onError:null},checkoutOptions:{}};window.HitPay={async init(e,l={},t={}){s||(n.defaultUrl=e,n.initOptions=l,n.callbacks=t,e=l.scheme||"https",t=l.domain||"hit-pay.com",l=l.path||"",(o=document.createElement("iframe")).setAttribute("src",e+`://${t}${l}/hitpay-iframe.html`),o.setAttribute("allow","payment"),o.style.position="fixed",o.style.border="0",o.style.width="100vw",o.style.height="100vh",o.style.margin="0",o.style.padding="0",o.style["z-index"]="99999999",o.style.top="0",o.style.left="0",o.style.display="none",document.body.appendChild(o),i=new Promise(e=>{a=e}))},async toggle(e){i&&await i,n.visible?document.body.style.overflow=t:(t=document.body.style.overflow,document.body.style.overflow="hidden",o.style.display="block");var l=n.visible?0:500;window.setTimeout(()=>{o.contentWindow.postMessage({type:"toggle",props:{defaultUrl:n.defaultUrl,...n.initOptions,checkoutOptions:e}},"*"),n.visible=!n.visible,n.visible||(o.style.display="none",n.callbacks.onClose&&n.callbacks.onClose())},l)}},window.onmessage=function(e){if(e.data)switch(e.data.type){case"loaded":i=null,a&&(s=!0,a());break;case"toggle":window.HitPay.toggle({});break;case"success":n.callbacks.onSuccess&&n.callbacks.onSuccess();break;case"error":n.callbacks.onError&&n.callbacks.onError(e.data.error)}}}bootstrap();