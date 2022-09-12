import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import Database from '@ioc:Adonis/Lucid/Database'
import InviteToMailTag from 'App/Mailers/InviteToMailTag'
import Invite from 'App/Models/Invite'
import User from 'App/Models/User'
import InviteEmailValidator from 'App/Validators/InviteEmailValidator'

export default class InviteController {
  public async invite({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { emails } = await request.validate(InviteEmailValidator)

    const invites = await Database.transaction(async (trx) => {
      return await Promise.all(
        emails.map((email) => {
          // TODO handle sending errors, check response then create maybe
          return Invite.firstOrCreate(
            { receiverEmail: email },
            { receiverEmail: email, senderEmail: user.email },
            { client: trx }
          )
        })
      )
    })

    await Promise.all(
      invites.map((invite) => {
        return new InviteToMailTag(invite.receiverEmail, user.username).sendLater()
      })
    )

    return {
      data: {
        message: 'Emails invitation successfully sent',
      },
    }
  }
}
