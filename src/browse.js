var assetList = new Vue({
    el: '#assetList',
    data: {
        creator:[],
        license:[],
        type:[],
        tags:"",
		order:"latest",
        offset:0,
		perPage:200,
		assetCache: [],
		totalNumberOfAssets:0,
		currentlyHoveringAsset:null
    },
    methods: {
		reset:function(){
			this.creator=[""];
			this.license=[""];
			this.type=[""];
			this.tags="";
			this.order="latest";
		},
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
				fetch('/api/v1/getAssets?include=tag,creator,license,type&asset='+assetId)
				.then(res => res.json())
				.then(out =>{
					self.currentlyHoveringAsset = out.result.assets[0];
				});
			}
		},
		getQueryVariables:function() {
			var query = window.location.hash.substring(1);
			var vars = query.split('&');
			var output = [];
			for (var i = 0; i < vars.length; i++) {
				var pair = vars[i].split('=');
				output[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1]);
			}
			return output;
		}
    },
	beforeMount(){
		params = this.getQueryVariables();
		this.creator = (params.creator ?? "").split(',');
		this.license = (params.license ?? "").split(',');
		this.type = (params.type ?? "").split(',');
		this.tags = decodeURIComponent(params.tags ?? "");
		this.order = (params.order ?? "latest");
	},
	watch:{
		hashQuery: function(){
			params = new URLSearchParams(this.hashQuery);
			let keysForDel = [];
			params.forEach((value, key) => {
				if (value == '') {
					keysForDel.push(key);
				}
			});

			keysForDel.forEach(key => {
			params.delete(key);
			});
			location.href = '#'+params.toString();
		}
	},
    computed:{
		apiQuery:function(){
			this.assetCache=[];
			this.offset = 0;
			window.scrollTo(0,0);
			return {
				creator: this.creator.join(','),
                license: this.license.join(','),
                type: this.type.join(','),
                tags: this.tags,
				sort:this.order
			};
		},
		hashQuery:function(){
			return {
				creator: this.creator.join(','),
                license: this.license.join(','),
                type: this.type.join(','),
                tags: encodeURI(this.tags),
				order:this.order
			};
		},
		queryPosition:function(){
			return {
				offset:this.offset,
				limit:this.perPage
			}
		},
        url:function(){
            params = new URLSearchParams(Object.assign({},this.apiQuery,this.queryPosition));
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
  });
function idleConfirmTags() {
	var t;
	var leftTagsBar =true;
	var tagsBar = document.getElementById('tagsBar');
	window.onload = resetTimer;
	window.onkeydown = resetTimer;

	function yourFunction() {
		
		if(tagsBar === document.activeElement ){
			leftTagsBar = false;
			document.getElementById('tagsBar').blur();
			document.getElementById('tagsBar').focus();
		}else{
			if(!leftTagsBar){
				document.getElementById('tagsBar').focus();
				document.getElementById('tagsBar').blur();
				leftTagsBar = true;
			}
		}
		resetTimer();
	}

	function resetTimer() {
	clearTimeout(t);
	t = setTimeout(yourFunction, 750);  // time is in milliseconds
	}
}
idleConfirmTags();