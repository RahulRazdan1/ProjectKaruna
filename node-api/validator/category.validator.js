const { body, param } = require('express-validator')
const { ObjectId } = require('mongoose').Types
const Category = require('../models/category.model')
const msg = require('./_message.json').category

const addCategoryValidate = () => {
    return [
        body('name').exists().withMessage(msg.category.nameRequired).isString().withMessage(msg.category.nameString).custom(value => {
            return new Promise((resolve, reject) => {
                Category.findOne({ name: value }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc) {
                            if (!doc.parentCategory || doc.parentCategory == null) {
                                reject(msg.category.nameExist)
                            } else {
                                reject(msg.subcategory.nameExist)
                            }

                        } else {
                            resolve(true)
                        }
                    }
                })
            })
        })
    ]
}

const addSubcategoryValidate = () => {
    return [
        // param("id").exists().withMessage(msg.subcategory.idRequired)
        body('name').exists().withMessage(msg.subcategory.nameRequired).isString().withMessage(msg.subcategory.nameString).custom(value => {
            return new Promise((resolve, reject) => {
                Category.findOne({ name: value }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc) {
                            if (!doc.parentCategory || doc.parentCategory == null) {
                                reject(msg.category.nameExist)
                            } else {
                                reject(msg.subcategory.nameExist)
                            }
                        } else {
                            resolve(true)
                        }
                    }
                })
            })
        }),
        body("parentCategory").exists().withMessage(msg.subcategory.parentCatRequired).isString().withMessage(msg.subcategory.parentCatString).isMongoId().withMessage(msg.subcategory.parentCatId).custom(value => {
            return new Promise((resolve, reject) => {
                Category.findOne({ _id: value }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc) {
                            if (!doc.parentCategory || doc.parentCategory == null) {
                                resolve(true)
                            } else {
                                reject(msg.subcategory.isSubCat)
                            }
                        } else {
                            reject(msg.subcategory.parentCatNotFound)
                        }
                    }
                })
            })
        })
    ]
}
const updateCategoryValidate = () => {
    return [
        param("id").exists().withMessage(msg.category.idRequired).isMongoId().withMessage(msg.valid.mongoId).custom(value => {
            return new Promise((resolve, reject) => {
                Category.findOne({ _id: ObjectId(value) }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc) {
                            if (!doc.parentCategory || doc.parentCategory == null) {
                                resolve(true)

                            } else {
                                reject(msg.category.invalidId)
                            }

                        } else {
                            reject(msg.category.invalidId)
                        }
                    }
                })
            })
        }),
        body('name').exists().withMessage(msg.category.nameRequired).isString().withMessage(msg.category.nameString).custom((value, { req }) => {
            return new Promise((resolve, reject) => {

                Category.find({ $and: [{ name: value }, { _id: { $ne: new ObjectId(req.params.id) } }] }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc.length > 0) {
                            for (let i = 0; i < doc.length; i++) {
                                if (!doc[i].parentCategory || doc[i].parentCategory == null) {
                                    reject(msg.category.nameExist)
                                } else {
                                    reject(msg.subcategory.nameExist)
                                }

                            }

                        } else {
                            resolve(true)
                        }
                    }
                })
            })
        })
    ]
}
const updateSubCategoryValidate = () => {
    return [
        param("id").exists().withMessage(msg.category.idRequired).isMongoId().withMessage(msg.valid.mongoId).custom(value => {
            return new Promise((resolve, reject) => {
                Category.findOne({ _id: ObjectId(value) }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc) {
                            if (!doc.parentCategory || doc.parentCategory == null) {
                                reject(msg.subcategory.invalidId)


                            } else {
                                resolve(true)
                            }

                        } else {
                            reject(msg.subcategory.invalidId)
                        }
                    }
                })
            })
        }),
        body('name').exists().withMessage(msg.category.nameRequired).isString().withMessage(msg.category.nameString).custom((value, { req }) => {
            return new Promise((resolve, reject) => {

                Category.find({ $and: [{ name: value }, { _id: { $ne: new ObjectId(req.params.id) } }] }, (error, doc) => {
                    if (error) {
                        reject(error)
                    } else {
                        if (doc.length > 0) {
                            for (let i = 0; i < doc.length; i++) {
                                if (!doc[i].parentCategory || doc[i].parentCategory == null) {
                                    reject(msg.category.nameExist)
                                } else {
                                    reject(msg.subcategory.nameExist)
                                }

                            }

                        } else {
                            resolve(true)
                        }
                    }
                })
            })
        })
    ]
}

const getCategoryValidator = () => {
    return [
        param("id").exists().withMessage(msg.category.idRequired).isMongoId().withMessage(msg.valid.mongoId)
    ]
}

module.exports = { addCategoryValidate, addSubcategoryValidate, updateCategoryValidate, updateSubCategoryValidate, getCategoryValidator }