const { body, validationResult, param } = require('express-validator')
const { ObjectId } = require('mongoose').Types
const user = require('../models/user.model')
const staticData = require('../util/staticData.json')

const loginValidate = () => {
    return [
        body('username').exists().withMessage("Username required").isString().withMessage("Username should be a string"),
        body('password').exists().withMessage("Password required"),
    ]
}

const createUserValidate = () => {
    return [
        body('firstName').exists().withMessage("First name required").isString().withMessage("First name should be a string"),
        body('lastName').exists().withMessage("Last name required").isString().withMessage("Last Name should be a string"),
        body('email').exists().withMessage("Email required").isEmail().withMessage("Please provide a valid email id").custom(value => {
            return new Promise((resolve, reject) => {
                user.findOne({ email: value }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc) {
                            reject("Email Id already exist")
                        } else {
                            resolve(true)
                        }
                    }
                })
            })

        }),
        body('mobile').exists().withMessage("Mobile No required").custom(value => {
            return new Promise((resolve, reject) => {
                user.findOne({ mobile: value }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc) {
                            reject("Mobile No already exist")
                        } else {
                            resolve(true)
                        }
                    }
                })
            })

        }),
        body('password').exists().withMessage("Password required").isLength({ min: 6 }).withMessage("Minimum length of password should be 6"),
        body('confirmPassword').exists().withMessage("Please confirm the password").custom((value, { req }) => {
            if (value !== req.body.password) {
                throw new Error('Password confirmation does not match password');
            }
            return true
        }),
        body('userType').exists().withMessage("User Type required").isString().withMessage("User type should be a string"),
        body('region').exists().withMessage("region required").isString().withMessage("region should be a string").custom(value => {
            return new Promise((resolve, reject) => {
                if (staticData.regions.indexOf(value) < 0) {
                    reject('Invalid region')
                } else {
                    resolve(true)
                }
            })
        }),
        body('address').exists().withMessage("address required").isString().withMessage("address should be string"),
        body('deviceType').exists().withMessage("deviceType required").isString().withMessage("deviceType should be a string"),
        body('deviceToken').exists().withMessage("deviceToken required").isString().withMessage("deviceToken should be a string")
    ]
}
const updateUserValidate = () => {
    return [
        param("id").exists().withMessage('UserId required').isMongoId().withMessage("This is not a valid id"),
        body('firstName').isString().withMessage("First name should be a string"),
        body('lastName').isString().withMessage("Last Name should be a string"),
        body('email').isEmail().withMessage("Please provide a valid email id").custom((value, { req }) => {
            return new Promise((resolve, reject) => {
                user.findOne({ $and: [{ email: value }, { _id: { $ne: new ObjectId(req.params.id) } }] }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc) {
                            reject("Email Id already exist")
                        } else {
                            resolve(true)
                        }
                    }
                })
            })

        }),
        body('mobile').custom((value, { req }) => {
            return new Promise((resolve, reject) => {
                user.findOne({ $and: [{ mobile: value }, { _id: { $ne: new ObjectId(req.params.id) } }] }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc) {
                            reject("Mobile No already exist")
                        } else {
                            resolve(true)
                        }
                    }
                })
            })

        }),
        // body('password').isLength({ min: 6 }).withMessage("Minimum length of password should be 6"),

        // body('userType').isString().withMessage("User type should be a string")
    ]
}

module.exports = { loginValidate, createUserValidate, updateUserValidate }