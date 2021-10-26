var assetList = new Vue({
    el: '#assetList',
    data: {
        creator:[],
        license:[],
        type:[],
        tags:[],
        offset:0,
		perPage:200,
		assetCache: [],
		totalNumberOfAssets:0,
		currentlyHoveringAsset:null
    },
    methods: {
        nextPage: function(){
            if(this.offset + this.perPage < this.assetData.totalNumberOfAssets){
				this.offset += this.perPage
			}
        },
		resetOffset: function(){
			this.offset=0;
		},
		setHoveringAssetData: function(assetId){
			if(this.currentlyHoveringAsset == null || assetId != this.currentlyHoveringAsset.assetId){
				var self = this;
				if(self.currentlyHoveringAsset != null){
					self.currentlyHoveringAsset.assetId = assetId;
				}
				fetch('/api/v1/getAssets?include=creator,license,type&asset='+assetId)
				.then(res => res.json())
				.then(out =>{
					self.currentlyHoveringAsset = out.result.assets[0];
				});
			}
		}
    },
    computed:{
		query:function(){
			this.assetCache=[];
			this.offset = 0;
			window.scrollTo(0,0);
			return {
				creator: this.creator.join(','),
                license: this.license.join(','),
                type: this.type.join(','),
                tags: this.tags
			};
		},
		queryPosition:function(){
			return {
				offset:this.offset,
				limit:this.perPage
			}
		},
        url:function(){
            params = new URLSearchParams(Object.assign({},this.query,this.queryPosition));
            return '/api/v1/getAssets?' + params.toString()
        },
        assetData: function(){
			var Httpreq = new XMLHttpRequest();
			Httpreq.open("GET",this.url,false);
			Httpreq.send(null);
			var result = JSON.parse(Httpreq.responseText).result
			this.assetCache = this.assetCache.concat(result.assets);
			this.totalNumberOfAssets = result.totalNumberOfAssets;
            return {
				assets:this.assetCache,
				totalNumberOfAssets:this.totalNumberOfAssets
			};   
        }
    }
  })



