
const modifyModel = async (req) => {
    try {
        const _id = req.user._id
        req.body.createdBy = _id
        req.body.modifiedBy = _id
        return req
    } catch (error) {
        throw error
    }
}
module.exports = { modifyModel }