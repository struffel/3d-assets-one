<!DOCTYPE html>
<html lang="en">
    <?php
        include $_SERVER['DOCUMENT_ROOT']."/components/head.php";
    ?>
    <body>
        <?php include $_SERVER['DOCUMENT_ROOT']."/components/header.php";?>
        <main id="aboutText" class="content container">
            
            <h2>What is 3Dassets.one?</h2>
            <p>
                3Dassets.one is a search engine that combines the libraries of many independent CG asset websites like polyhaven, ambientCG and others and allows you to look for the right texture, HDRI or model without having to open half a dozen browser tabs.
            </p>

            <h2>How does this site work?</h2>
            <p>
                The site automatically polls the websites of all creators for new assets and automatically lists them. You can filter the results by creator or using keywords, categories and licenses. When clicking on an asset you will be redirected to the creators website.
                3Dassets.one does not copy, archive or re-upload the assets.
            </p>

            <h2>Meet the creators featured on 3Dassets.one:</h2>
            <p>
                <div v-for="c in creators" class="box is-flex is-align-items-center">
                    
                    <div class="image is-64x64 mr-5"><img v-bind:src="'https://cdn3.struffelproductions.com/file/3D-Assets-One/creator-icon/64-PNG/'+c.CreatorId+'.png'" v-bind:alt="c.CreatorName + ' logo'"></div>
                    <span>
                        <p>
                            <strong>{{c.CreatorName}}</strong><br>
                            {{c.CreatorDescription}}
                        </p>
                        
                        <p>
                            <a class="button is-small" v-bind:href="'/#creator='+c.CreatorSlug">Browse all {{c.AssetCount}} assets</a>
                            <a class="button is-small" v-bind:href="c.BaseUrl">Visit creator website</a>
                        </p>
                        
                    </span>
                </div>
            </p>

            <h2>Who is building this website?</h2>
            <p>
                3Dassets.one is a side project to ambientCG.com. Check the <a href="https://ambientCG.com/legal">Imprint</a> for details.
            </p>

        </main>
        <script src="./src/about.js"></script>
    </body>
</html>