<!DOCTYPE html>
<html>
<head>
    <title>Test Disease Detection</title>
</head>
<body>
    <h1>Test Disease Detection API</h1>
    <form id="testForm" enctype="multipart/form-data">
        <input type="file" name="image" accept="image/*" required><br><br>
        <select name="cropId">
            <option value="">No Crop</option>
            <option value="1">Test Crop 1</option>
        </select><br><br>
        <button type="submit">Analyze</button>
    </form>
    <div id="result"></div>

    <script>
    document.getElementById('testForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        const fileInput = document.querySelector('input[name="image"]');
        const cropSelect = document.querySelector('select[name="cropId"]');
        
        formData.append('image', fileInput.files[0]);
        if (cropSelect.value) {
            formData.append('cropId', cropSelect.value);
        }
        
        try {
            const response = await fetch('/api/disease/analyze.php', {
                method: 'POST',
                body: formData
            });
            
            const text = await response.text();
            console.log('Raw response:', text);
            
            let result;
            try {
                result = JSON.parse(text);
            } catch(e) {
                document.getElementById('result').innerHTML = '<pre>Parse Error:\n' + text + '</pre>';
                return;
            }
            
            document.getElementById('result').innerHTML = '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
        } catch (error) {
            document.getElementById('result').innerHTML = '<pre>Error: ' + error.message + '</pre>';
            console.error(error);
        }
    });
    </script>
</body>
</html>
