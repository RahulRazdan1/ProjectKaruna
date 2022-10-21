const { ObjectId } = require('mongoose').Types
const Category = require('../models/category.model')
const NotFoundError = require('../_errorHandler/404')
const categoryImageFileConfig = require('../config/fileConfig.json').categoryImage
const addCategoryDb = async (reqBody) => {
    try {
        const category = new Category(reqBody)
        return category.save()
    } catch (error) {
        throw error
    }
}

const updateCategoryDb = async (reqBody, id) => {
    try {
        const update = await Category.updateOne({ _id: ObjectId(id) }, reqBody).exec()
        console.log(update)
        return true
    } catch (error) {
        throw error
    }
}
const getCategoryDetailsDb = async (segment, id) => {
    try {
        let query
        let errMsg
        if (segment == "getCategory") {
            query = { $and: [{ _id: new ObjectId(id) }, { parentCategory: null }] }
            errMsg = "No Category Found"
        } else if (segment == "getSubcategory") {
            query = { $and: [{ _id: new ObjectId(id) }, { parentCategory: { $ne: null } }] }
            errMsg = "No Sub-Category Found"
        }
        const category = await Category.findOne(query).exec()
        if (!category || category == null) {
            throw new NotFoundError(errMsg)
        }
        category.categoryImage = categoryImageFileConfig.path + '/' + category.categoryImage
        return category
    } catch (error) {
        throw error
    }
}
const getSubCategoryDetailsDb = async (segment, id) => {
    try {
        let query
        let errMsg
        if (segment == "getCategory") {
            query = { $and: [{ _id: new ObjectId(id) }, { parentCategory: null }] }
            errMsg = "No Category Found"
        } else if (segment == "getSubcategory") {
            query = { $and: [{ _id: new ObjectId(id) }, { parentCategory: { $ne: null } }] }
            errMsg = "No Sub-Category Found"
        }
        const category = await Category.findOne(query).exec()
        if (!category || category == null) {
            throw new NotFoundError(errMsg)
        }
        category.categoryImage = categoryImageFileConfig.path + '/' + category.categoryImage
        return category
    } catch (error) {
        throw error
    }
}
const getAllCategoryDetailsDb = async () => {
    try {
        const result = await Category.find({ parentCategory: null }).exec()
        if (!result || result == null) {
            throw new NotFoundError("Category Not Found")
        }
        for (let i = 0; i < result.length; i++) {
            result[i].categoryImage = categoryImageFileConfig.path + '/' + result[i].categoryImage

        }
        return result
    } catch (error) {
        throw error
    }
}
const getAllSubCategoryDetailsDb = async (id) => {
    try {
        // const result = await Category.find({ parentCategory: new ObjectId(id) }).exec()
        const result = await Category.aggregate([
            { $match: { parentCategory: new ObjectId(id) } },
            {
                $lookup: {
                    from: 'categories',
                    localField: "parentCategory",
                    foreignField: '_id',
                    as: 'categoryDetails'
                }
            },
            { $unwind: "$categoryDetails" },
            {
                $addFields: {
                    categoryName: '$categoryDetails.name',
                    subCategoryId: '$categoryDetails._id'
                }
            }
        ]).exec()
        if (!result || result == null) {
            throw new NotFoundError("Sub-Category Not Found")
        }
        for (let i = 0; i < result.length; i++) {
            delete result[i].categoryDetails
            result[i].categoryImage = categoryImageFileConfig.path + '/' + result[i].categoryImage

        }
        return result
    } catch (error) {
        throw error
    }
}

module.exports = { addCategoryDb, updateCategoryDb, getCategoryDetailsDb, getAllCategoryDetailsDb, getAllSubCategoryDetailsDb, getSubCategoryDetailsDb }