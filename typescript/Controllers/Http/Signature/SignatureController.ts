import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import Database from '@ioc:Adonis/Lucid/Database'
import MilestoneEvent, { EventType } from 'App/Models/MileStoneEvent'
import Signature from 'App/Models/Signature'
import User from 'App/Models/User'
import GetSignatureByIdValidator from 'App/Validators/Signature/GetSignatureByIdValidator'
import UpdateSignatureValidator from 'App/Validators/Signature/UpdateSignatureValidator'

export default class SignatureController {
  public async index({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!

    const signatures = await Signature.query().where({ userId: user.id })

    return {
      data: {
        signatures: signatures.map((signature) => signature.serializedSignatureBasicInfo),
      },
    }
  }

  public async show({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetSignatureByIdValidator)

    const signature = await Signature.query()
      .where({ id: params.id, userId: user.id })
      .preload('user')
      .firstOrFail()

    return {
      data: {
        signature: signature.serializedSignatureInfo,
      },
    }
  }

  public async create({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!

    const {
      name,
      title,
      avatarUrl,
      company,
      email,
      avatarBorderRadiusType,
      website,
      officeAddress,
      officePhoneNumber,
      homePhoneNumber,
      bannerUrl,
    } = request.body()

    const signature = await Signature.create({
      userId: user.id,
      name,
      title,
      avatarUrl,
      company,
      email,
      avatarBorderRadiusType,
      website,
      officeAddress,
      officePhoneNumber,
      homePhoneNumber,
      bannerUrl,
    })

    const pingMilestone = { userId: user.id, eventType: EventType.signatureCreated }
    await MilestoneEvent.firstOrCreate(pingMilestone, pingMilestone)

    return {
      data: {
        message: 'Signature created successfully',
        signature: signature.serializedSignatureBasicInfo,
      },
    }
  }

  public async update({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetSignatureByIdValidator)
    const updateAttributes = await request.validate(UpdateSignatureValidator)

    const userSignatures = await Signature.query().where({ userId: user.id })

    const updatedSignature = await Database.transaction(async (trx) => {
      const isDefaultChanged = updateAttributes.isDefault

      if (isDefaultChanged) {
        await Promise.all(
          userSignatures
            .filter((userSignature) => userSignature.isDefault)
            .map((userSignature) =>
              userSignature.useTransaction(trx).merge({ isDefault: false }).save()
            )
        )
      }

      const signatureToUpdate = userSignatures.find(
        (userSignature) => userSignature.id === params.id
      )!
      const updated = await signatureToUpdate.useTransaction(trx).merge(updateAttributes).save()

      return updated
    })

    return {
      data: {
        message: 'Signature updated successfully',
        signature: updatedSignature.serializedSignatureBasicInfo,
      },
    }
  }

  public async destroy({ auth, request }: HttpContextContract) {
    const { id: userId } = auth.use('api').user!
    const { params } = await request.validate(GetSignatureByIdValidator)

    const signature = await Signature.query().where({ id: params.id, userId }).firstOrFail()

    // TODO: Consider raising error if the signature to be deleted is default

    await signature.delete()

    /**
     * TODO: queue a delete milestone background job
     * If user has no ping email records, delete the user's milestone_event
     *
     * We might consider not doing this, since the user is already onboarded. No point in rollinback
     * their onboarding progress
     */

    return {
      data: {
        message: 'Signature deleted successfully',
      },
    }
  }
}
