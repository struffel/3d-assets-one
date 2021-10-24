var assetList = new Vue({
    el: '#assetList',
    data: {
        creator:"",
        license:"",
        type:"",
        tags:"",
        offset:0,
		perPage:100
    },
    methods: {
        nextPage: function(){
            if(this.offset + this.perPage < this.assetData.totalNumberOfAssets){
				this.offset += this.perPage
			}
        },
        previousPage: function(){
			tmp = this.offset - this.perPage;
			this.offset = Math.max(0,tmp);
        },
		resetOffset: function(){
			this.offset=0;
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
        assetData: function(){
            var Httpreq = new XMLHttpRequest();
            Httpreq.open("GET",this.url,false);
            console.log(this.url);
            Httpreq.send(null);
            return JSON.parse(Httpreq.responseText).result;   
        }
    }
  })



