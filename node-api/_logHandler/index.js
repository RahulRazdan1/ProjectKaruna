const { createLogger, format, transports } = require('winston');
const { combine, timestamp, printf } = format;
const get_ip = require('ipware')().get_ip;

const timezoned = () => {
    return new Date().toUTCString();
}

const logFormat = printf(info => {
    return `${info.timestamp} [${info.level}] [ message : ${JSON.stringify(info.message)} ]`;
});
const resLogFormat = printf(info => {
    return `${info.timestamp} [${info.level}] [RESULT] [ ip : ${JSON.stringify(info.message.ip)} ] : [ params : ${JSON.stringify(info.message.reqParams)} ] [ body : ${JSON.stringify(info.message.reqBody)} ]: [ message : ${JSON.stringify(info.message)} ]`;
});
const reqLogFormat = printf(info => {
    return `${info.timestamp} [${info.level}] [RESQUEST] [ ip : ${JSON.stringify(info.message.ip)} ] : [ params : ${JSON.stringify(info.message.reqParams)} ] [ body : ${JSON.stringify(info.message.reqBody)} ]: [ url : ${JSON.stringify(info.message.url)} ] [ message : ${JSON.stringify(info.message)} ]`;
});

const logger = createLogger({
    format: combine(
        // format.json(),
        format.timestamp({ format : timezoned }),
        // timestamp({ format: new Date().toUTCString() }),
        logFormat,
    ),
    transports: [
        new transports.Console(),
        new transports.File({ filename: 'logs/error.log', level: 'error' }),
        new transports.File({ filename: 'logs/combined.log' }),
    ]
});

const resLogger = createLogger({
    format: combine(
        // format.json(),
        format.timestamp({ format : timezoned }),
        // timestamp({ format: new Date().toUTCString() }),
        resLogFormat,
    ),
    transports: [
        new transports.Console(),
        new transports.File({ filename: 'logs/error.log', level: 'error' }),
        new transports.File({ filename: 'logs/combined.log' }),
    ]
});


const reqLogger = createLogger({
    format: combine(
        // format.json(),
        format.timestamp({ format : timezoned }),
        // timestamp({ format: new Date().toUTCString() }),
        reqLogFormat,
    ),
    transports: [
        new transports.Console(),
        new transports.File({ filename: 'logs/error.log', level: 'error' }),
        new transports.File({ filename: 'logs/combined.log' }),
    ]
});

const loggerFun = (req, res, next) => {
    let log = []
    log.reqParams = req.params
    log.reqBody = req.body
    log.url = req.url
    log.timestamp = new Date().toUTCString()
    log.ip = get_ip(req).clientIp
    
    reqLogger.info(log)
    next();
}




module.exports = { logger, resLogger, reqLogger, loggerFun };