const { Schema, Types, model } = require('mongoose')
const { ObjectId } = Types

const CategorySchema = new Schema({
    name: {
        type: String,
        required: true,
    },
    parentCategory: {
        type: ObjectId,
        default: null,
    },
    categoryImage: {
        type: String,
        required: true
    },
    cretedAt: {
        type: Date,
        default: new Date()
    },
    createdBy: {
        type: ObjectId
    },
    modifiedAt: {
        type: Date,
        default: new Date()
    },
    modifiedBy: {
        type: ObjectId,
        default: null
    },
    isActive: {
        type: Boolean,
        default: true
    }
})

CategorySchema.pre("updateOne", async function (next) {
    let category = this
    delete category._update.createdBy
    category._update.modifiedAt = Date.now()
    console.log(category._update)
    next()
})


const Category = model('Categories', CategorySchema)
module.exports = Category
