const { addCategoryDb, updateCategoryDb, getCategoryDetailsDb, getAllCategoryDetailsDb, getAllSubCategoryDetailsDb, getSubCategoryDetailsDb } = require("../manager/category.manager")
const { successResponse, failureResponse } = require("../services/generateResponse")
const { modifyModel } = require('../helper/modifyModel')
const successMsg = require('../util/_message.json').successMessage.category
const fileConfig = require('../config/fileConfig.json')

const addCategory = async (req, res) => {
    try {
        req.body.categoryImage = req.file.filename
        const data = await modifyModel(req)
        const result = await addCategoryDb(data.body)
        result.categoryImage = fileConfig.categoryImage.path + '/' + result.categoryImage
        successResponse(req, res, result, successMsg.saved)
    } catch (error) {
        console.log(error);
        failureResponse(req, res, error)
    }
}


const updateCategory = async (req, res) => {
    try {
        if (req.file) {
            req.body.categoryImage = req.file.filename
        }

        const catId = req.params.id
        const data = await modifyModel(req)
        const result = await updateCategoryDb(data.body, catId)
        successResponse(req, res, result, successMsg.update)
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const getCategoryDetails = async (req, res) => {
    try {
        const segment = req.url.split('/')[1]
        const result = await getCategoryDetailsDb(segment, req.params.id)
        let msg = ""
        if (segment == "getCategory") {
            msg = successMsg.categoryFound
        } else {
            msg = successMsg.subCategoryFound
        }
        successResponse(req, res, result, msg)
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const getSubCategoryDetails = async (req, res) => {
    try {
        const segment = req.url.split('/')[1]
        const result = await getSubCategoryDetailsDb(segment, req.params.id)
        let msg = ""
        if (segment == "getCategory") {
            msg = successMsg.categoryFound
        } else {
            msg = successMsg.subCategoryFound
        }
        successResponse(req, res, result, msg)
    } catch (error) {
        failureResponse(req, res, error)
    }
}

const getAllCategory = async (req, res) => {
    try {
        const result = await getAllCategoryDetailsDb()
        successResponse(req, res, result, 'Categories fetched')
        return result
    } catch (error) {
        failureResponse(req, res, error)
    }
}


const getAllSubCategory = async (req, res) => {
    try {
        const result = await getAllSubCategoryDetailsDb(req.params.id)
        successResponse(req, res, result, 'Sub-Categories fetched')
        return result
    } catch (error) {
        failureResponse(req, res, error)
    }
}

module.exports = { addCategory, updateCategory, getCategoryDetails, getAllCategory, getAllSubCategory, getSubCategoryDetails }