<head>
<!-- development version, includes helpful console warnings -->
    <?php
        //echo '<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>';
        echo '<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.min.js"></script>';
    ?>
    <style>
        @import url("./style/bulma.min.css");
        @import url('https://fonts.googleapis.com/css2?family=Red+Hat+Display:wght@700&display=swap');

        .scaleHover{
            transition: transform ease-in-out 0.1s,opacity ease-in-out .2s;
        }
        .scaleHoverStrong:hover{
            transform: scale(1.05);
        }
        .scaleHoverSubtle:hover{
            transform: scale(1.0125);
        }
        .logo{
            font-family: 'Red Hat Display', sans-serif;
        }
    </style>

    <title>3Dassets.one - The asset search engine</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="apple-touch-icon" sizes="180x180" href="https://cdn3.struffelproductions.com/file/3D-Assets-One/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://cdn3.struffelproductions.com/file/3D-Assets-One/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://cdn3.struffelproductions.com/file/3D-Assets-One/favicon/favicon-16x16.png">
    <link rel="manifest" href="https://cdn3.struffelproductions.com/file/3D-Assets-One/favicon/site.webmanifest">
    <link rel="mask-icon" href="https://cdn3.struffelproductions.com/file/3D-Assets-One/favicon/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="https://cdn3.struffelproductions.com/file/3D-Assets-One/favicon/favicon.ico">
    <meta name="msapplication-TileColor" content="#2d89ef">
    <meta name="msapplication-config" content="https://cdn3.struffelproductions.com/file/3D-Assets-One/favicon/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
</head>