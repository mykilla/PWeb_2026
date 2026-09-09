const express = require('express');
const path = require('path');
const app = express();
const port = 3000;

// Setup supaya Express mengenali folder client sebagai public statis
app.use(express.static(path.join(__dirname, '../client')));

app.listen(port, () => {
  console.log(`Server udah jalan di http://localhost:${port}`);
});