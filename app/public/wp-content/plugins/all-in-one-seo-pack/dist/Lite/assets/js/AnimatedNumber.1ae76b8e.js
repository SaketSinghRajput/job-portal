import{_ as n,o as a,c as u,t as o}from"./_plugin-vue_export-helper.2d9794a3.js";const m={props:{number:Number,fromNumber:{type:Number,default(){return 0}},formatNumber:{type:Boolean,default(){return!0}}},data(){return{animatedNumber:0}},watch:{number(){this.animateNumber()}},computed:{formattedNumber(){return this.formatNumber?this.$numbers.numberFormat(this.animatedNumber):this.animatedNumber}},methods:{animateNumber(){const t=this.$numbers.animateNumbers(this.fromNumber,this.number,e=>this.animatedNumber=e);window.addEventListener("blur",()=>{t.cancel(),this.animatedNumber=this.number})}},mounted(){this.animateNumber()}};function s(t,e,i,c,l,r){return a(),u("span",null,o(r.formattedNumber),1)}const b=n(m,[["render",s]]);export{b as U};
