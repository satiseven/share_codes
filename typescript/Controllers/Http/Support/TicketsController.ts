import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import SupportTicket from 'App/Models/SupportTicket'
import SupportTicketsCreateValidator from 'App/Validators/Support/SupportTicketsCreateValidator'

export default class SupportController {
  public async create({ auth, request, response }: HttpContextContract) {
    const { id: userId } = auth.use('api').user!

    const attributes = await request.validate(SupportTicketsCreateValidator)

    // TODO: remove this model infavor of background queue, and write the ticket into 3rd party
    // software
    await SupportTicket.create({
      userId,
      ...attributes,
    })

    return response.status(202).send({
      data: {
        message: 'Ticket created',
      },
    })
  }
}
