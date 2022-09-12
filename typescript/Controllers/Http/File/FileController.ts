import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import FileException from 'App/Exceptions/FileException'
import FileUploadValidator from 'App/Validators/File/FileUploadValidator'

export default class FileController {
  public async upload({ request }: HttpContextContract) {
    const { file } = await request.validate(FileUploadValidator)

    if (!file.isValid) {
      throw new FileException('The file selected is broken, please try another file!', 422)
    }

    await file.moveToDisk('./')

    return {
      data: {
        message: 'File uploaded successfully',
        url: file.filePath,
      },
    }
  }
}
