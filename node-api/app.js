const express = require('express');
const https = require('https')
const fs = require('fs')
const app = express()
const bodyParser = require('body-parser');
const urlencodedParser = bodyParser.urlencoded({ extended: false })
app.use(bodyParser.json())
const cors = require('cors')
app.use(cors())
app.use(urlencodedParser)
app.use(express.static(__dirname + '/public'));
const morgan = require('morgan');
const PORT = process.env.PORT || 2000
const chalk = require('chalk')
const get_ip = require('ipware')().get_ip;

const { logger, reqLogger, loggerFun } = require('./_logHandler')

var timeout = require('connect-timeout'); //express v4

app.use(timeout(8000));
app.use(haltOnTimedout);

function haltOnTimedout(req, res, next){
  if (!req.timedout){
      console.log()
  }
  next();
}
  
const { connect } = require('./connection')
connect()

app.use(loggerFun);
// loggerFun(req, res, next)

app.use('/', require('./routes/index'))
app.use('/public', express.static('public'));
//end
app.use("*", (req, res) => {
    const error = {
        success: false,
        msg: "404-Not Found",
        error: "The end point you are looking for is not found"
    }
    res.status(404).send(error)
})


//certificate files for ssl connection
const privateKey = fs.readFileSync('./SSL/privkey1.pem');
const certificate = fs.readFileSync('./SSL/fullchain1.pem');
// const ca = fs.readFileSync('./SSL/chain1.pem');
let os = require("os");


// https.createServer({
//     key: privateKey,
//     cert: certificate,
//     // ca: ca
// }, app).listen(PORT, () => {
//     console.log(`Karuna(Production) is running at PORT: ${PORT} `)
// }); // work if the application is running at server


// app.use('/', require('./routes/index'))

app.listen(PORT, () => {
    console.log(`Application is running at PORT: ${PORT}`)
})

