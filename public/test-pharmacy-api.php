<!DOCTYPE html>
<html>
<head>
    <title>Test Medicine API</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Test Medicine API</h1>
    <button onclick="testAPI(1)">Get Medicine ID=1</button>
    <button onclick="testAPI(4)">Get Medicine ID=4</button>
    <button onclick="testAPI(0)">Get Medicine ID=0</button>
    <pre id="result"></pre>
    
    <script>
    function testAPI(id) {
        $('#result').text('Loading...');
        $.ajax({
            url: '/unidia/public/pharmacy/get-medicine?id=' + id,
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                $('#result').text(JSON.stringify(res, null, 2));
            },
            error: function(xhr) {
                $('#result').text('Error: ' + xhr.status + ' - ' + xhr.responseText);
            }
        });
    }
    </script>
</body>
</html>