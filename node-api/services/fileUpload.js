const multer = require('multer');
const InternalServerError = require('../_errorHandler/500');
const randomstring = require('randomstring')
const path = require('path');
const fs = require('fs')
const { failureResponse } = require('./generateResponse');
const uploadConfig = require('../config/uploadFileConfig.json');
const { body } = require('express-validator');

let ownerConfig = []

const storage = multer.diskStorage({
    destination: function (req, file, callback) {
        if (file) {
            const fileDir = ownerConfig.storagePath
            //create new folder if not present
            if (!fs.existsSync(fileDir)) {
                fs.mkdirSync(fileDir, { recursive: true });
            }
        } else {
            callback(null, false)
        }
        callback(null, ownerConfig.storagePath);
    },
    filename: function (req, file, callback) {
        callback(null, 'ngo_' + Date.now() + '_' + randomstring.generate(10) + path.extname(file.originalname));
    }
});

const upload = multer({
    storage: storage,
    fileFilter(req, file, cb) {
        if (!file.originalname.match(/\.(jpg|jpeg|png)$/)) {
            //Error 
            return cb({ status: 0, msg: 'Only images are allowed' });
        } else {
            cb(undefined, true)
        }
        //Success 
    }
})




const fileUpload = (fileName, owner) => {
    let executed = false

    if (uploadConfig.owners.includes(owner)) {
        ownerConfig = uploadConfig[owner]
        executed = true
    } else {
        executed = false

    }
    return (req, res, next) => {
        if (executed) {
            const uploading = upload.single(fileName)
            uploading(req, res, (err) => {
                if (err) {
                    console.log('error in image uploading')
                    failureResponse(req, res, err)
                } else {
                    next()
                }
            })
        } else {
            let error = new InternalServerError('Provided Upload owner not configued')
            failureResponse(req, res, error)
        }

    }

}


const fileConfig = require('../config/fileConfig.json');

const storage1 = multer.diskStorage({
    destination: function (req, file, callback) {
        if (file) {
            const fileDir = 'public' + fileConfig[file.fieldname].path
            //create new folder if not present
            if (!fs.existsSync(fileDir)) {
                fs.mkdirSync(fileDir, { recursive: true });
            }
            callback(null, fileDir);
        } else {
            callback(null, false)
        }

    },
    filename: function (req, file, callback) {
        callback(null, fileConfig[file.fieldname].prefix + Date.now() + '_' + randomstring.generate(15) + path.extname(file.originalname));
    }
});


const upload1 = multer({
    storage: storage1,
    fileFilter: (req, file, callback) => {
        let ext = path.extname(file.originalname)
        ext = ext.replace('.', '')
        if (fileConfig[file.fieldname].supportExt.indexOf(ext) > -1) {
            callback(null, true)
        } else {
            callback(new BadRequestError({ _ErrCode: '2WI_FILE_UPLOAD_ERROR', message: `File format for ${fileConfig[file.fieldname].nick_name} not supported` }), false)
        }
    },

})

const uploadSingleFile = (fileName) => {
    return (req, res, next) => {
        const uploading = upload1.single(fileName)
        uploading(req, res, (err) => {
            if (err) {
                failureResponse(req, res, err)
            } else {
                next()
            }
        })

    }

}

module.exports = { fileUpload, uploadSingleFile }