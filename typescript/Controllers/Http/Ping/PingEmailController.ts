import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import PingEmail from 'App/Models/PingEmail'
import User from 'App/Models/User'
import CreatePingEmailValidator from 'App/Validators/Ping/CreatePingEmailValidator'
import GetPingEmailByIdValidator from 'App/Validators/Ping/GetPingEmailByIdValidator'
import UpdatePingEmailValidator from 'App/Validators/Ping/UpdatePingEmailValidator'
import SearchPingEmailValidator from 'App/Validators/Ping/SearchPingEmailValidator'
import MilestoneEvent, { EventType } from 'App/Models/MileStoneEvent'

export default class PingEmailController {
  public async index({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!

    const pingEmails = await PingEmail.query().where({ userId: user.id })

    return {
      data: {
        pingEmails: pingEmails.map((pingEmail) => pingEmail.serializedPingEmailInfo),
      },
    }
  }

  public async show({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetPingEmailByIdValidator)

    const pingEmail = await PingEmail.query()
      .where({ id: params.id, userId: user.id })
      .firstOrFail()

    return {
      data: {
        pingEmail: pingEmail.serializedPingEmailInfo,
      },
    }
  }

  public async create({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const {
      recipient,
      name,
      subject,
      status,
      gmailMessageId,
      gmailThreadId,
      emailId,
      pingSequenceId,
    } = await request.validate(CreatePingEmailValidator)

    const pingEmail = await PingEmail.create({
      userId: user.id,
      recipient,
      name,
      subject,
      status,
      gmailMessageId,
      gmailThreadId,
      emailId,
      pingSequenceId,
    })

    // TODO: this event is also fired from post mailgun endpoint
    const pingMilestone = { userId: user.id, eventType: EventType.pingCreated }
    await MilestoneEvent.firstOrCreate(pingMilestone, pingMilestone)

    return {
      data: {
        message: 'Ping email created successfully',
        pingEmail: pingEmail.serializedPingEmailInfo,
      },
    }
  }

  public async update({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetPingEmailByIdValidator)
    const pingEmail = await PingEmail.query()
      .where({ id: params.id, userId: user.id })
      .firstOrFail()
    const updateAttrs = await request.validate(UpdatePingEmailValidator)

    const updatedPingEmail = await pingEmail.merge(updateAttrs).save()

    return {
      data: {
        message: 'Ping email update successfully',
        updatedPingEmail,
      },
    }
  }

  public async destroy({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetPingEmailByIdValidator)
    const pingEmail = await PingEmail.query()
      .where({ id: params.id, userId: user.id })
      .firstOrFail()

    await pingEmail.delete()

    /**
     * TODO: queue a delete milestone background job
     * If user has no ping email records, delete the user's milestone_event
     *
     * We might consider not doing this, since the user is already onboarded. No point in rollinback
     * their onboarding progress
     */

    return {
      data: {
        message: 'Ping email deleted successfully',
      },
    }
  }

  public async start({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetPingEmailByIdValidator)
    const pingEmail = await PingEmail.query()
      .where({ id: params.id, userId: user.id })
      .firstOrFail()

    // TODO add this operation to SQS or queue service
    await pingEmail.merge({ status: 'started' }).save()
    await pingEmail.refresh()

    return {
      data: {
        message: 'Ping email started successfully',
        pingEmailStatus: pingEmail.status,
      },
    }
  }

  public async stop({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetPingEmailByIdValidator)
    const pingEmail = await PingEmail.query()
      .where({ id: params.id, userId: user.id })
      .firstOrFail()

    // TODO add this operation to SQS or queue service
    await pingEmail.merge({ status: 'stopped' }).save()
    await pingEmail.refresh()

    return {
      data: {
        message: 'Ping email stopped successfully',
        pingEmailStatus: pingEmail.status,
      },
    }
  }

  public async restart({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetPingEmailByIdValidator)
    const pingEmail = await PingEmail.query()
      .where({ id: params.id, userId: user.id })
      .firstOrFail()

    // TODO add this operation to SQS or queue service
    await pingEmail.merge({ status: 'stopped' }).save()
    await pingEmail.merge({ status: 'started' }).save()
    await pingEmail.refresh()

    return {
      data: {
        message: 'Ping email restarted successfully',
        pingEmailStatus: pingEmail.status,
      },
    }
  }

  public async search({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { searchTerm } = await request.validate(SearchPingEmailValidator)

    const pingEmails = await PingEmail.query()
      .where({ userId: user.id })
      .andWhere((query) => {
        query.whereILike('subject', `%${searchTerm}%`).orWhereILike('recipient', `%${searchTerm}%`)
      })
      .orderBy([{ column: 'created_at', order: 'desc' }])

    return {
      data: {
        pingEmails: pingEmails.map((pingEmail) => pingEmail.serializedPingEmailInfo),
      },
    }
  }
}
