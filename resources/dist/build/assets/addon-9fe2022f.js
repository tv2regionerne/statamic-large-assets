function h(t,e,s,o,r,d,c,u){var a=typeof t=="function"?t.options:t;e&&(a.render=e,a.staticRenderFns=s,a._compiled=!0),o&&(a.functional=!0),d&&(a._scopeId="data-v-"+d);var i;if(c?(i=function(n){n=n||this.$vnode&&this.$vnode.ssrContext||this.parent&&this.parent.$vnode&&this.parent.$vnode.ssrContext,!n&&typeof __VUE_SSR_CONTEXT__<"u"&&(n=__VUE_SSR_CONTEXT__),r&&r.call(this,n),n&&n._registeredComponents&&n._registeredComponents.add(c)},a._ssrRegister=i):r&&(i=u?function(){r.call(this,(a.functional?this.parent:this).$root.$options.shadowRoot)}:r),i)if(a.functional){a._injectStyles=i;var l=a.render;a.render=function(_,f){return i.call(f),l(_,f)}}else{var p=a.beforeCreate;a.beforeCreate=p?[].concat(p,i):[i]}return{exports:t,options:a}}const m={mixins:[Fieldtype],data(){return{}},computed:{acceptMimeTypes(){return null},canUpload(){return this.config.allow_uploads&&(this.can("configure asset containers")||this.can("upload "+this.container+" assets"))}},methods:{openUpload(){this.$refs.upload.click()},fileSelected(t){if(!this.canUpload)return;const e=t.target.files||t.dataTransfer.files;if(!e.length)return;const s=e[0];this.fileUpload(s)},async fileUpload(t){const e=this.freshAxios(),s=this.config.container,o=t.name,r=t.size,d=cp_url("large-assets/api/upload/create"),c=await e.post(d,{container:s,path:o,size:r}),u=c.data.uploadId,a=await Promise.all(c.data.parts.map(async l=>{const p=t.slice(l.start,l.end),n=await e.put(l.url,p);return{...l,eTag:n.headers.etag}})),i=cp_url("large-assets/api/upload/complete");await e.post(i,{container:s,path:o,size:r,uploadId:u,parts:a})},freshAxios(){const t=this.$axios.create();return delete t.defaults.headers.common["X-CSRF-TOKEN"],delete t.defaults.headers.common["X-Requested-With"],t}}};var g=function(){var e=this,s=e._self._c;return s("div",[e.canUpload?s("input",{ref:"upload",staticClass:"hidden",attrs:{type:"file",accept:e.acceptMimeTypes},on:{input:e.fileSelected}}):e._e(),e.canUpload?s("button",{staticClass:"btn btn-with-icon grow w-full",attrs:{type:"button",tabindex:"0"},on:{click:e.openUpload,keyup:function(o){return!o.type.indexOf("key")&&e._k(o.keyCode,"space",32,o.key,[" ","Spacebar"])&&e._k(o.keyCode,"enter",13,o.key,"Enter")?null:e.openUpload.apply(null,arguments)}}},[s("svg-icon",{staticClass:"w-4 h-4 text-gray-800",attrs:{name:"regular/folder-image"}}),s("span",{staticClass:"tv2r-sm"},[e._v(e._s(e.__("Upload Media")))])],1):e._e()])},y=[],C=h(m,g,y,!1,null,null,null,null);const U=C.exports;Statamic.booting(()=>{Statamic.component("large_assets-fieldtype",U)});
