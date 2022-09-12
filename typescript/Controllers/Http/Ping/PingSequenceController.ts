import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import PingSequence from 'App/Models/PingSequence'
import User from 'App/Models/User'
import CreatePingSequenceValidator from 'App/Validators/Ping/CreatePingSequenceValidator'
import GetPingSequenceByIdValidator from 'App/Validators/Ping/GetPingSequenceByIdValidator'
import UpdatePingSequenceValidator from 'App/Validators/Ping/UpdatePingSequenceValidator'

export default class PingSequenceController {
  public async index({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!

    const pingSequences = await PingSequence.query()
      .where({ userId: user.id })
      .preload('pingSequenceDetails')
      .orderBy('created_at', 'asc')

    return {
      data: {
        pingSequences: pingSequences.map((pingSequence) => pingSequence.serializedPingSequenceInfo),
      },
    }
  }

  public async show({ request }: HttpContextContract) {
    const { params } = await request.validate(GetPingSequenceByIdValidator)
    const pingSequence = await PingSequence.query()
      .where('id', params.id)
      .preload('pingSequenceDetails')
      .firstOrFail()

    return {
      data: {
        pingSequence: pingSequence.serializedPingSequenceInfo,
        pingSequenceDetails: pingSequence.pingSequenceDetails.map(
          (pingSequenceDetail) => pingSequenceDetail.serializedPingSequenceDetailInfo
        ),
      },
    }
  }

  public async create({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { name, duration, timezone } = await request.validate(CreatePingSequenceValidator)
    const pingSequence = await PingSequence.create({ userId: user.id, name, duration, timezone })

    await pingSequence.load('pingSequenceDetails') // Fixme: This would be always empty

    return {
      data: {
        message: 'Ping sequence created successfully',
        pingSequence: pingSequence.serializedPingSequenceInfo,
      },
    }
  }

  public async update({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetPingSequenceByIdValidator)
    const updateAttrs = await request.validate(UpdatePingSequenceValidator)

    const pingSequence = await PingSequence.query()
      .where({ userId: user.id, id: params.id })
      .preload('pingSequenceDetails')
      .firstOrFail()
    const updatedPingSequence = await pingSequence.merge(updateAttrs).save()

    return {
      data: {
        message: 'Ping sequence updated successfully',
        pingSequence: updatedPingSequence.serializedPingSequenceInfo,
      },
    }
  }

  public async destroy({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetPingSequenceByIdValidator)
    const pingSequence = await PingSequence.query()
      .where({ userId: user.id, id: params.id })
      .firstOrFail()

    await pingSequence.delete()

    return {
      data: {
        message: 'Ping sequence deleted successfully',
      },
    }
  }
}
