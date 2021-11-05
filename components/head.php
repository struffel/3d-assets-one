<head>
<!-- development version, includes helpful console warnings -->
    <?php
        echo '<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>';
        //echo '<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.min.js"></script>';
    ?>
    <style>
        @import url("./style/bulma.min.css");
        @import url('https://fonts.googleapis.com/css2?family=Red+Hat+Display:wght@700&display=swap');

        .thumbnail{
            transition: transform ease-in-out 0.1s,opacity ease-in-out .2s;
        }
        .thumbnail:hover{
            transform: scale(1.05);
        }
        .logo{
            font-family: 'Red Hat Display', sans-serif;
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>