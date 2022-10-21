const get_ip = require('ipware')().get_ip;
const { resLogger, logger } = require('../_logHandler')
const successResponse = (req, res, data, msg) => {
    let response = {
        success: true,
        msg: msg,
        data: data
    }
    if (data === true) {
        delete response.data
    }
    let log = response
    log.reqParams = req.params
    log.reqBody = req.body
    log.url = req.url
    log.timestamp = new Date().toUTCString()
    log.ip = get_ip(req).clientIp

    resLogger.info(log)
    logger.info("LOGGING EVENT FINISHED.")
    res.status(200).send(response)
}
const failureResponse = (req, res, error) => {
    let log = error
    log.reqParams = req.params
    log.reqBody = req.body
    log.url = req.url
    log.timestamp = new Date().toUTCString()
    log.ip = log.ip = get_ip(req).clientIp

    logger.info("LOGGING EVENT FINISHED.")
    resLogger.info(log)
    error.statusCode = error.statusCode || 500
    let response = {
        success: false,
        msg: error.message,
        error: error.name
    }
    res.status(error.statusCode).send(response)
}

module.exports = { successResponse, failureResponse }
