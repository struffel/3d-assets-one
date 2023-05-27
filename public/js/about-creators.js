var assetList = new Vue({
    el: '#aboutText',
    data: {
    },
    computed:{
        creators:function(){
            var Httpreq = new XMLHttpRequest();
            Httpreq.open("GET","/api/v1/getCreators",false);
            Httpreq.send(null);
            return JSON.parse(Httpreq.responseText).result;
        }
    }
});