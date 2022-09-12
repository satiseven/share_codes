import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import ReadEmail from 'App/Models/ReadEmail'
import UnreadEmail from 'App/Models/UnreadEmail'
import User from 'App/Models/User'
import GetReadEmailByIdValidator from 'App/Validators/Email/GetReadEmailByIdValidator'
import GetUnreadEmailByIdValidator from 'App/Validators/Email/GetUnreadEmailByIdValidator'

export default class InboxController {
  public async getEmails({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const [readEmails, unreadEmails] = await Promise.all([
      ReadEmail.query()
        .where({ userId: user.id })
        .preload('activities', (query) => query.orderBy('created_at', 'desc'))
        .preload('email'),
      UnreadEmail.query()
        .where({ userId: user.id })
        .preload('activities', (query) => query.orderBy('created_at', 'desc'))
        .preload('email'),
    ])

    return {
      data: {
        readEmails: readEmails.map((readEmail) => readEmail.serializedReadEmailInfo),
        unreadEmails: unreadEmails.map((unreadEmail) => unreadEmail.serializedUnreadEmailInfo),
      },
    }
  }

  public async getReadEmailById({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetReadEmailByIdValidator)
    const readEmail = await ReadEmail.query()
      .where({ id: params.id, userId: user.id })
      .preload('activities', (query) => query.orderBy('created_at', 'desc'))
      .preload('email')
      .firstOrFail()

    return {
      data: {
        readEmail: readEmail.serializedReadEmailInfo,
      },
    }
  }

  public async getUnreadEmailById({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetUnreadEmailByIdValidator)
    const unreadEmail = await UnreadEmail.query()
      .where({ id: params.id, userId: user.id })
      .preload('activities', (query) => query.orderBy('created_at', 'desc'))
      .preload('email')
      .firstOrFail()

    return {
      data: {
        unreadEmail: unreadEmail.serializedUnreadEmailInfo,
      },
    }
  }
}
