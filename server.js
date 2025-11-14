// server.js - Simple Node.js Server
const http = require('http');
const fs = require('fs');
const path = require('path');

const port = 3000;
const publicDir = path.join(__dirname, 'public');

const server = http.createServer((req, res) => {
    let filePath = path.join(publicDir, req.url === '/' ? 'index.html' : req.url);
    
    // Kiểm tra file tồn tại
    fs.access(filePath, fs.constants.F_OK, (err) => {
        if (err) {
            res.writeHead(404);
            res.end('File not found');
            return;
        }

        // Đọc và trả về file
        fs.readFile(filePath, (err, data) => {
            if (err) {
                res.writeHead(500);
                res.end('Server error');
                return;
            }

            // Set content type
            const ext = path.extname(filePath);
            const contentTypes = {
                '.html': 'text/html',
                '.css': 'text/css',
                '.js': 'application/javascript',
                '.png': 'image/png',
                '.jpg': 'image/jpeg'
            };
            
            res.writeHead(200, {
                'Content-Type': contentTypes[ext] || 'text/plain'
            });
            res.end(data);
        });
    });
});

server.listen(port, () => {
    console.log(`🚀 Server running at http://localhost:${port}/`);
    console.log(`📁 Serving files from: ${publicDir}`);
});