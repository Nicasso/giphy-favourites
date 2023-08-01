<!doctype html>
<html lang="en">
<head>
    <title>Giphys</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
</head>
<body>

<div class="toast-container position-absolute top-0 end-0 p-3">
    <div class="toast " role="alert" aria-live="assertive" aria-atomic="true" data-autohide="true">
        <div class="toast-header">
            <strong class="me-auto toast-title"></strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">

        </div>
    </div>
</div>

<div class="container">
    <h1>Giphy favourites</h1>

    <button class="btn btn-primary" id="fetch-giphys">
        Fetch Giphys
        <div class="spinner-border" role="status">
            <span class="sr-only"></span>
        </div>
    </button>
</div>
<div class="container">
    <h2>Favourites</h2>
    <div id="giphys" class="row giphy-grid">

    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="./js/script.js"></script>
</body>
</html>
