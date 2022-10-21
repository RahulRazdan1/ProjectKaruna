const express = require('express')
const { addCategory, updateCategory, getCategoryDetails, getAllCategory, getAllSubCategory, getSubCategoryDetails } = require('../controllers/category.controller')
const router = express.Router()
const { authentication } = require('../services/auth.service')
const { addCategoryValidate, addSubcategoryValidate, updateCategoryValidate, updateSubCategoryValidate, getCategoryValidator } = require('../validator/category.validator')
const { checkValidationResult } = require('../validator/expressvalidator')
const { uploadSingleFile } = require('../services/fileUpload')

router.post('/addCategory', uploadSingleFile('categoryImage'), addCategoryValidate(), checkValidationResult, authentication, addCategory)
router.post('/addSubcategory', uploadSingleFile('categoryImage'), addSubcategoryValidate(), checkValidationResult, authentication, addCategory)
router.post('/updateCategory/:id', uploadSingleFile('categoryImage'), updateCategoryValidate(), checkValidationResult, authentication, updateCategory)
router.post('/updateSubcategory/:id', uploadSingleFile('categoryImage'), updateSubCategoryValidate(), checkValidationResult, authentication, updateCategory)
router.get('/getCategory/:id', getCategoryValidator(), checkValidationResult, getCategoryDetails)
router.get('/getSubcategory/:id', getCategoryValidator(), checkValidationResult, getCategoryDetails) //getCategoryDetails 1/2
router.get('/getAllCategory', getAllCategory)
router.get('/getAllSubCategory/:id', getCategoryValidator(), checkValidationResult, getAllSubCategory)

module.exports = router