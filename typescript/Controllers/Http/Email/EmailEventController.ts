import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import EmailEvent from 'App/Models/EmailEvent'
import CreateEmailEventValidator from 'App/Validators/Email/CreateEmailEventValidator'
import GetEmailEventByIdValidator from 'App/Validators/Email/GetEmailEventByIdValidator'

export default class EmailEventController {
  public async show({ request }: HttpContextContract) {
    const { params } = await request.validate(GetEmailEventByIdValidator)
    const emailEvent = await EmailEvent.query().where({ id: params.id }).firstOrFail()

    return {
      data: {
        emailEvent,
      },
    }
  }

  public async create({ request }: HttpContextContract) {
    const { emailId, device, userAgent, location } = await request.validate(
      CreateEmailEventValidator
    )
    const emailEvent = await EmailEvent.create({ emailId, device, userAgent, location })

    return {
      data: {
        message: 'Email event created successfully',
        emailEvent,
      },
    }
  }

  public async destroy({ request }: HttpContextContract) {
    const { params } = await request.validate(GetEmailEventByIdValidator)
    const emailEvent = await EmailEvent.query().where({ id: params.id }).firstOrFail()

    await emailEvent.delete()

    return {
      data: {
        message: 'Email event deleted successfully',
      },
    }
  }
}
