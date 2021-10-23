var assetList = new Vue({
    el: '#assetList',
    data: {
        creator:"",
        license:"",
        type:"",
        tags:"",
        offset:0,
        numberOfAssets:5
    },
    methods: {
        nextPage: function(){
            this.offset += 100
        },
        previousPage: function(){
            this.offset -= 100
        }
    },
    computed:{
        url:function(){
            params = new URLSearchParams({
                creator: this.creator,
                license: this.license,
                type: this.type,
                tags: this.tags,
                offset:this.offset
              });
            return '/api/v1/getAssets?' + params.toString()
        },
        assets: function(){
            var Httpreq = new XMLHttpRequest(); // a new request
            Httpreq.open("GET",this.url,false);
            console.log(this.url);
            Httpreq.send(null);
            return JSON.parse(Httpreq.responseText).result.assets;   
        }
    }
  })



