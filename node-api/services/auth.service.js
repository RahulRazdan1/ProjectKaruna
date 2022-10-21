const jwt = require('jsonwebtoken')
const User = require('../models/user.model')
const jwtSecret = require('../config/appConfig.json').JWT_SECRCET
const UnauthorizedError = require('../_errorHandler/401')
const { failureResponse } = require('./generateResponse')

const chalk = require('chalk')

const userTypes = require('../util/staticData.json').userType

const authentication = async (req, res, next) => {
    const header = req.headers.authorization
    try {
        const decoded = jwt.verify(header, jwtSecret);
        const id = decoded.data._id
        let user = await User.findById(id).exec()
        if (!user || user == null) {
            throw new UnauthorizedError("Unauthicated")
        } else {
            user = JSON.parse(JSON.stringify(user))
            delete user.password
            req.user = user
            next()
        }

    } catch (err) {
        err.statusCode = 401
        failureResponse(req, res, err)
    }

}

const donerAuth = async (req, res, next) => {
    try {
        if (req.user.userType == userTypes[0]) {
            next()
        } else {
            throw new UnauthorizedError("Only doner can access to this functionalities")
        }
    } catch (err) {
        err.statusCode = 401
        failureResponse(req, res, err)
    }
}
const receiverAuth = async (req, res, next) => {
    try {
        if (req.user.userType == userTypes[1]) {
            next()
        } else {
            throw new UnauthorizedError("Only receiver can access to this functionalities")
        }
    } catch (err) {
        err.statusCode = 401
        failureResponse(req, res, err)
    }
}
const volunteerAuth = async (req, res, next) => {
    try {
        if (req.user.userType == userTypes[2]) {
            next()
        } else {
            // throw new UnauthorizedError("Only volunteer can access to this functionalities")
            console.log(chalk.red('!!! Important: ') + chalk.yellow('uncomment the commented line to authorize volunteer. It is commented temporarily.[auth.services.js-> line No: 60]'))
            next()
        }
    } catch (err) {
        err.statusCode = 401
        failureResponse(req, res, err)
    }
}

module.exports = { authentication, donerAuth, receiverAuth, volunteerAuth }