const staticData = require('../util/staticData.json')
const { successResponse, failureResponse } = require("../services/generateResponse")
const { logger } = require("../_logHandler")
const appConfig = (req, res) => {
    try {
        logger.info("STATIC DATA CALLED");
        let data = {}
        data.regions = staticData.regions
        data.deliveryTypes = staticData.deliveryTypes
        data.requestStatus = staticData.requestStatus
        successResponse(req, res, data, 'Initial config')
    } catch (error) {
        failureResponse(req, res, error)
    }

}

module.exports = { appConfig }