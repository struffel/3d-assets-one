<!DOCTYPE html>
<html lang="en">
    <?php
        include $_SERVER['DOCUMENT_ROOT']."/../components/head.php";
    ?>
    <body>
        <?php include $_SERVER['DOCUMENT_ROOT']."/../components/header.php";?>
        <main id="aboutText" class="content container">
            
            <div v-for="c in creators" class="box is-bordered is-flex is-align-items-center">
                
                <div class="image is-64x64 mr-5"><img v-bind:src="'https://3d1-media.struffelproductions.com/file/3D-Assets-One/creator-icon/64-PNG/'+c.CreatorId+'.png'" v-bind:alt="c.CreatorName + ' logo'"></div>
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
            

        </main>
        <script src="./js/about-creators.js"></script>
    </body>
</html>