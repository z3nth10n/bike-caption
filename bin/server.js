#!/usr/bin/env node

var server = require('../lib/index.js');

const fileName = process.argv.slice(process.execArgv + 2)[0];
const filePath = './index.html';
if (fileName) {
  filePath = `./${fileName}`;
}

server.SimpleNodeServer(filePath);